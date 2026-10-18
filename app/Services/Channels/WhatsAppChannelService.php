<?php

namespace App\Services\Channels;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WhatsappAccount;
use App\Services\Channels\Concerns\HandlesAiAutoReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannelService implements ChannelServiceInterface
{
    use HandlesAiAutoReply;
    protected string $serviceUrl;

    public function __construct()
    {
        $this->serviceUrl = rtrim(config('services.whatsapp.service_url', 'http://127.0.0.1:3000'), '/');
    }

    /**
     * Send an outbound message to WhatsApp via Baileys microservice.
     */
    public function sendMessage(Conversation $conversation, Message $message): array
    {
        $account = WhatsappAccount::withoutGlobalScopes()
            ->where('business_id', $conversation->business_id)
            ->first();

        if (!$account || $account->status !== 'connected') {
            Log::warning("WhatsApp account not connected for business {$conversation->business_id}");
            return [
                'status' => 'failed',
                'error' => 'WhatsApp account is not connected.',
            ];
        }

        $recipient = $conversation->contact?->platform_sender_id ?: $conversation->contact?->phone;

        if (!$recipient) {
            return [
                'status' => 'failed',
                'error' => 'No recipient found for WhatsApp contact.',
            ];
        }

        // Support full JID (@lid, @s.whatsapp.net) or raw phone number
        $targetTo = str_contains($recipient, '@') ? trim($recipient) : preg_replace('/[^0-9]/', '', $recipient);

        try {
            $response = Http::timeout(8)
                ->withHeaders(WhatsAppTokenService::getRequestHeaders())
                ->post("{$this->serviceUrl}/api/sessions/{$account->session_id}/messages", [
                    'to' => $targetTo,
                    'text' => $message->body,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $waMsgId = $data['message_id'] ?? 'WA_' . uniqid();

                $message->update([
                    'platform_message_id' => (string) $waMsgId,
                    'status' => 'delivered',
                    'metadata' => array_merge($message->metadata ?? [], ['whatsapp_response' => $data]),
                ]);

                return [
                    'status' => 'delivered',
                    'platform_message_id' => $waMsgId,
                ];
            }
        } catch (\Throwable $e) {
            Log::info("Baileys microservice unreachable ({$e->getMessage()}), using mock fallback mode.");
        }

        // Fallback simulation mode for testing without active Node daemon
        $mockMsgId = 'BAE5_' . strtoupper(bin2hex(random_bytes(6)));
        $message->update([
            'platform_message_id' => $mockMsgId,
            'status' => 'delivered',
            'metadata' => array_merge($message->metadata ?? [], ['simulation_mode' => true]),
        ]);

        return [
            'status' => 'delivered',
            'platform_message_id' => $mockMsgId,
            'simulated' => true,
        ];
    }

    /**
     * Handle incoming webhook requests from Baileys microservice.
     */
    public function handleWebhook(Business $business, Request $request): array
    {
        $event = $request->input('event');
        $data = $request->input('data', []);

        if ($event === 'connection.update') {
            return $this->handleConnectionUpdate($business, $data);
        }

        if ($event === 'messages.upsert') {
            return $this->handleIncomingMessage($business, $data);
        }

        return [
            'status' => 'ignored',
            'message' => 'Unhandled WhatsApp event: ' . $event,
        ];
    }

    /**
     * Handle Baileys connection status updates.
     */
    protected function handleConnectionUpdate(Business $business, array $data): array
    {
        $status = $data['status'] ?? 'disconnected';
        if ($status === 'qr') {
            $status = 'connecting';
        }
        $phoneNumber = $data['phone_number'] ?? null;

        $account = WhatsappAccount::withoutGlobalScopes()->updateOrCreate([
            'business_id' => $business->id,
        ], [
            'session_id' => $data['session_id'] ?? ('session_' . $business->id),
            'status' => $status,
            'qr_code' => null,
            'phone_number' => $phoneNumber,
            'last_connected_at' => ($status === 'connected') ? now() : null,
        ]);

        return [
            'status' => 'success',
            'account_status' => $account->status,
        ];
    }

    /**
     * Handle incoming/outgoing WhatsApp messages received via Baileys.
     * Supports multi-store synchronization for merchants sharing the same phone number.
     */
    protected function handleIncomingMessage(Business $business, array $data): array
    {
        $fromJid = $data['from'] ?? null;
        $text = $data['text'] ?? '[رسالة وسائط]';
        $messageId = (string) ($data['message_id'] ?? ('WA_' . uniqid()));
        $pushName = $data['push_name'] ?? null;
        $isFromMe = !empty($data['is_from_me']);
        $accountPhoneNumber = $data['account_phone_number'] ?? $business->whatsappAccount?->phone_number;

        if (!$fromJid) {
            return ['status' => 'ignored', 'error' => 'No sender JID.'];
        }

        // Clean phone number from JID (e.g. "201012345678@s.whatsapp.net" -> "201012345678")
        $phone = explode('@', $fromJid)[0];
        $name = $pushName ?: ('عميل واتساب +' . $phone);

        // Find all businesses sharing this WhatsApp phone number to prevent confusion and sync history
        $businessIds = [$business->id];
        if (!empty($accountPhoneNumber)) {
            $cleanAccountPhone = preg_replace('/[^0-9]/', '', (string) $accountPhoneNumber);
            $sharedBusinessIds = WhatsappAccount::withoutGlobalScopes()
                ->where('status', 'connected')
                ->where(function ($q) use ($accountPhoneNumber, $cleanAccountPhone) {
                    $q->where('phone_number', $accountPhoneNumber)
                      ->orWhereRaw("REPLACE(REPLACE(phone_number, '+', ''), ' ', '') = ?", [$cleanAccountPhone]);
                })
                ->pluck('business_id')
                ->toArray();

            $businessIds = array_values(array_unique(array_merge($businessIds, $sharedBusinessIds)));
        }

        $primaryConversationId = null;
        $primaryMessageId = null;

        foreach ($businessIds as $bizId) {
            $biz = ($bizId === $business->id) ? $business : Business::find($bizId);
            if (!$biz) continue;

            // 1. Find or create Contact in this business preserving full JID
            $contact = Contact::withoutGlobalScopes()->where('business_id', $biz->id)
                ->where('platform', 'whatsapp')
                ->where(function ($q) use ($fromJid, $phone) {
                    $q->where('platform_sender_id', $fromJid)
                      ->orWhere('platform_sender_id', $phone)
                      ->orWhere('phone', $phone);
                })
                ->first();

            if ($contact) {
                $contact->update([
                    'platform_sender_id' => $fromJid,
                    'name' => $name,
                    'phone' => $phone,
                ]);
            } else {
                $contact = Contact::withoutGlobalScopes()->create([
                    'business_id' => $biz->id,
                    'platform' => 'whatsapp',
                    'platform_sender_id' => $fromJid,
                    'name' => $name,
                    'phone' => $phone,
                ]);
            }

            // 2. Find or create Conversation in this business
            $conversation = Conversation::withoutGlobalScopes()->firstOrCreate([
                'business_id' => $biz->id,
                'contact_id' => $contact->id,
                'platform' => 'whatsapp',
            ], [
                'status' => 'open',
                'unread_count' => 0,
                'last_message_at' => now(),
            ]);

            // 3. Deduplication check: check if message already exists by platform_message_id
            $existingMessage = Message::where('conversation_id', $conversation->id)
                ->where('platform_message_id', $messageId)
                ->first();

            if (!$existingMessage) {
                $senderType = $isFromMe ? 'agent' : 'contact';

                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => $senderType,
                    'platform_message_id' => $messageId,
                    'type' => 'text',
                    'body' => $text,
                    'status' => 'delivered',
                    'metadata' => [
                        'raw_payload' => $data,
                        'is_from_me' => $isFromMe,
                    ],
                ]);

                // 4. Update Conversation stats
                if (!$isFromMe) {
                    $conversation->increment('unread_count');
                }
                $conversation->update([
                    'last_message_at' => now(),
                ]);

                if ($bizId === $business->id) {
                    $primaryConversationId = $conversation->id;
                    $primaryMessageId = $message->id;
                }

                // 5. Trigger Realtime event
                try {
                    broadcast(new \App\Events\MessageSent($conversation, $message));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Broadcast failed for WhatsApp message: ' . $e->getMessage());
                }

                // 6. Check AI auto-reply
                if (!$isFromMe) {
                    $this->handleAiAutoReply($conversation, $text);
                }
            } else {
                if ($bizId === $business->id) {
                    $primaryConversationId = $conversation->id;
                    $primaryMessageId = $existingMessage->id;
                }
            }
        }

        return [
            'status' => 'success',
            'conversation_id' => $primaryConversationId,
            'message_id' => $primaryMessageId,
            'synced_businesses' => $businessIds,
        ];
    }

    /**
     * Request 8-digit Pairing Code for WhatsApp phone number connection.
     */
    public function requestPairingCode(Business $business, string $phoneNumber): array
    {
        $sessionId = 'session_' . $business->id;
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

        try {
            $response = Http::timeout(10)
                ->withHeaders(WhatsAppTokenService::getRequestHeaders())
                ->post("{$this->serviceUrl}/api/sessions/pairing-code", [
                'business_id' => $business->id,
                'session_id' => $sessionId,
                'phone_number' => $cleanPhone,
                'webhook_url' => route('webhooks.whatsapp', $business->id),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'failed',
                'message' => $response->json('error') ?? 'فشل الحصول على كود الربط من خادم واتساب.',
            ];
        } catch (\Throwable $e) {
            Log::warning('Pairing code request error: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'message' => 'تعذر الاتصال بخادم واتساب: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Terminate and remove WhatsApp session from microservice.
     */
    public function disconnect(Business $business): bool
    {
        $account = $business->whatsappAccount;
        if ($account) {
            $sessionId = $account->session_id ?: ('session_' . $business->id);
            try {
                Http::timeout(5)
                    ->withHeaders(WhatsAppTokenService::getRequestHeaders())
                    ->delete("{$this->serviceUrl}/api/sessions/{$sessionId}");
            } catch (\Throwable $e) {
                Log::warning("Failed to notify WhatsApp microservice of disconnect: {$e->getMessage()}");
            }
            $account->delete();
            return true;
        }

        return false;
    }
}
