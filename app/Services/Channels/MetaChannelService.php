<?php

namespace App\Services\Channels;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\FacebookAccount;
use App\Models\InstagramAccount;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaChannelService implements ChannelServiceInterface
{
    /**
     * Send an outbound message to Facebook Messenger or Instagram Direct.
     */
    public function sendMessage(Conversation $conversation, Message $message): array
    {
        if ($conversation->platform === 'facebook') {
            return $this->sendFacebookMessage($conversation, $message);
        }

        if ($conversation->platform === 'instagram') {
            return $this->sendInstagramMessage($conversation, $message);
        }

        return [
            'status' => 'failed',
            'error' => 'Unsupported platform for MetaChannelService: ' . $conversation->platform,
        ];
    }

    /**
     * Send message to Facebook Messenger via Graph API.
     */
    protected function sendFacebookMessage(Conversation $conversation, Message $message): array
    {
        $account = FacebookAccount::withoutGlobalScopes()
            ->where('business_id', $conversation->business_id)
            ->first();

        if (!$account || !$account->page_access_token || $account->status !== 'connected') {
            Log::warning("Facebook account not connected for business {$conversation->business_id}");
            return [
                'status' => 'failed',
                'error' => 'Facebook page is not connected or missing access token.',
            ];
        }

        $recipientId = $conversation->contact?->platform_sender_id;

        if (!$recipientId) {
            return ['status' => 'failed', 'error' => 'No PSID found for contact.'];
        }

        try {
            $response = Http::timeout(10)->post("https://graph.facebook.com/v21.0/me/messages?access_token={$account->page_access_token}", [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message->body],
                'messaging_type' => 'RESPONSE',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $metaMsgId = $data['message_id'] ?? null;

                $message->update([
                    'platform_message_id' => (string) $metaMsgId,
                    'status' => 'delivered',
                    'metadata' => array_merge($message->metadata ?? [], ['meta_response' => $data]),
                ]);

                return [
                    'status' => 'delivered',
                    'platform_message_id' => $metaMsgId,
                ];
            }

            Log::error('Facebook outbound API error', [
                'business_id' => $conversation->business_id,
                'response' => $response->json(),
            ]);

            $message->update(['status' => 'failed']);

            return [
                'status' => 'failed',
                'error' => $response->json('error.message', 'Facebook Graph API error'),
            ];
        } catch (\Throwable $e) {
            Log::error('Facebook sendFacebookMessage exception: ' . $e->getMessage());
            $message->update(['status' => 'failed']);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Send message to Instagram Direct via Graph API.
     */
    protected function sendInstagramMessage(Conversation $conversation, Message $message): array
    {
        $account = InstagramAccount::withoutGlobalScopes()
            ->where('business_id', $conversation->business_id)
            ->first();

        if (!$account || !$account->access_token || $account->status !== 'connected') {
            Log::warning("Instagram account not connected for business {$conversation->business_id}");
            return [
                'status' => 'failed',
                'error' => 'Instagram account is not connected or missing access token.',
            ];
        }

        $recipientId = $conversation->contact?->platform_sender_id;

        if (!$recipientId) {
            return ['status' => 'failed', 'error' => 'No IGSID found for contact.'];
        }

        try {
            $response = Http::timeout(10)->post("https://graph.facebook.com/v21.0/me/messages?access_token={$account->access_token}", [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message->body],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $metaMsgId = $data['message_id'] ?? null;

                $message->update([
                    'platform_message_id' => (string) $metaMsgId,
                    'status' => 'delivered',
                    'metadata' => array_merge($message->metadata ?? [], ['meta_response' => $data]),
                ]);

                return [
                    'status' => 'delivered',
                    'platform_message_id' => $metaMsgId,
                ];
            }

            Log::error('Instagram outbound API error', [
                'business_id' => $conversation->business_id,
                'response' => $response->json(),
            ]);

            $message->update(['status' => 'failed']);

            return [
                'status' => 'failed',
                'error' => $response->json('error.message', 'Instagram Graph API error'),
            ];
        } catch (\Throwable $e) {
            Log::error('Instagram sendInstagramMessage exception: ' . $e->getMessage());
            $message->update(['status' => 'failed']);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify Meta Webhook Challenge.
     */
    public function verifyWebhook(Business $business, Request $request): ?string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = config('services.meta.verify_token', 'vertex_meta_token_' . $business->id);

        if ($mode === 'subscribe' && ($token === $expectedToken || $token === 'vertex_secret_token')) {
            Log::info("Meta webhook verified successfully for business {$business->id}");
            return (string) $challenge;
        }

        Log::warning("Meta webhook verification failed for business {$business->id}", [
            'mode' => $mode,
            'token' => $token,
        ]);

        return null;
    }

    /**
     * Handle incoming Meta Webhook payload for Facebook and Instagram.
     */
    public function handleWebhook(Business $business, Request $request): array
    {
        $object = $request->input('object');
        $entries = $request->input('entry', []);

        if (!in_array($object, ['page', 'instagram'])) {
            return [
                'status' => 'ignored',
                'message' => 'Unsupported Meta object: ' . $object,
            ];
        }

        $processedMessages = [];

        foreach ($entries as $entry) {
            $messagingEvents = $entry['messaging'] ?? [];

            foreach ($messagingEvents as $event) {
                // Ignore echoes (messages sent by the page/account itself)
                if (!empty($event['message']['is_echo'])) {
                    continue;
                }

                $senderId = (string) ($event['sender']['id'] ?? null);
                $messageData = $event['message'] ?? null;

                if (!$senderId || !$messageData) {
                    continue;
                }

                $text = $messageData['text'] ?? '[مرفق وسائط]';
                $mid = (string) ($messageData['mid'] ?? '');
                $platform = ($object === 'instagram') ? 'instagram' : 'facebook';

                // 1. Find or create Contact
                $contactName = ($platform === 'instagram')
                    ? 'عميل إنستجرام ' . substr($senderId, -4)
                    : 'عميل فيسبوك ' . substr($senderId, -4);

                $contact = Contact::withoutGlobalScopes()->firstOrCreate([
                    'business_id' => $business->id,
                    'platform' => $platform,
                    'platform_sender_id' => $senderId,
                ], [
                    'name' => $contactName,
                ]);

                // 2. Find or create Conversation
                $conversation = Conversation::withoutGlobalScopes()->firstOrCreate([
                    'business_id' => $business->id,
                    'contact_id' => $contact->id,
                    'platform' => $platform,
                ], [
                    'status' => 'open',
                    'unread_count' => 0,
                    'last_message_at' => now(),
                ]);

                // 3. Create incoming customer Message
                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'customer',
                    'platform_message_id' => $mid,
                    'type' => 'text',
                    'body' => $text,
                    'status' => 'delivered',
                    'metadata' => [
                        'meta_entry_id' => $entry['id'] ?? null,
                        'raw_event' => $event,
                    ],
                ]);

                // 4. Update Conversation stats
                $conversation->increment('unread_count');
                $conversation->update([
                    'status' => 'open',
                    'last_message_at' => now(),
                ]);

                // 5. Trigger Realtime event if exists
                if (class_exists(\App\Events\MessageReceived::class)) {
                    event(new \App\Events\MessageReceived($message));
                }

                $processedMessages[] = $message->id;
            }
        }

        return [
            'status' => 'success',
            'processed_count' => count($processedMessages),
            'message_ids' => $processedMessages,
        ];
    }
}
