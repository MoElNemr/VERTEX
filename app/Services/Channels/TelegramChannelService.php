<?php

namespace App\Services\Channels;

use App\Events\MessageSent;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TelegramAccount;
use App\Services\Channels\Concerns\HandlesAiAutoReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannelService implements ChannelServiceInterface
{
    use HandlesAiAutoReply;

    /**
     * Send an outbound message to Telegram.
     */
    public function sendMessage(Conversation $conversation, Message $message): array
    {
        $account = TelegramAccount::withoutGlobalScopes()
            ->where('business_id', $conversation->business_id)
            ->first();

        if (! $account || ! $account->bot_token || $account->status !== 'connected') {
            Log::warning("Telegram account not connected for business {$conversation->business_id}");

            return [
                'status' => 'failed',
                'error' => 'Telegram account is not connected.',
            ];
        }

        $chatId = $conversation->contact?->platform_sender_id;

        if (! $chatId) {
            return [
                'status' => 'failed',
                'error' => 'No platform sender ID found for contact.',
            ];
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$account->bot_token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message->body,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $telegramMsgId = $data['result']['message_id'] ?? null;

                $message->update([
                    'platform_message_id' => (string) $telegramMsgId,
                    'status' => 'delivered',
                    'metadata' => array_merge($message->metadata ?? [], ['telegram_response' => $data]),
                ]);

                return [
                    'status' => 'delivered',
                    'platform_message_id' => $telegramMsgId,
                ];
            }

            Log::error('Telegram outbound API error', [
                'business_id' => $conversation->business_id,
                'response' => $response->json(),
            ]);

            $message->update(['status' => 'failed']);

            return [
                'status' => 'failed',
                'error' => $response->json('description', 'Unknown Telegram API error'),
            ];
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception: '.$e->getMessage());

            $message->update(['status' => 'failed']);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle an incoming webhook update from Telegram.
     */
    public function handleWebhook(Business $business, Request $request): array
    {
        $data = $request->all();

        // Check for regular message or edited message
        $telegramMessage = $data['message'] ?? $data['edited_message'] ?? null;

        if (! $telegramMessage) {
            return [
                'status' => 'ignored',
                'message' => 'No supported message payload found in update.',
            ];
        }

        $chat = $telegramMessage['chat'] ?? [];
        $from = $telegramMessage['from'] ?? [];
        $fromId = (string) ($from['id'] ?? $chat['id'] ?? null);

        if (! $fromId) {
            return [
                'status' => 'ignored',
                'error' => 'No sender ID in Telegram payload.',
            ];
        }

        // Construct contact name
        $name = trim(($from['first_name'] ?? '').' '.($from['last_name'] ?? ''));
        if (empty($name)) {
            $name = $from['username'] ?? 'عميل تيليجرام '.substr($fromId, -4);
        }

        $text = $telegramMessage['text'] ?? $telegramMessage['caption'] ?? '[مرفق بدون نص]';
        $telegramMsgId = (string) ($telegramMessage['message_id'] ?? '');

        // 1. Find or create Contact
        $contact = Contact::withoutGlobalScopes()->firstOrCreate([
            'business_id' => $business->id,
            'platform' => 'telegram',
            'platform_sender_id' => $fromId,
        ], [
            'name' => $name,
        ]);

        // 2. Find or create Conversation
        $conversation = Conversation::withoutGlobalScopes()->firstOrCreate([
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'platform' => 'telegram',
        ], [
            'status' => 'open',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);

        // 3. Create incoming customer Message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'contact',
            'platform_message_id' => $telegramMsgId,
            'type' => 'text',
            'body' => $text,
            'status' => 'delivered',
            'metadata' => [
                'telegram_update_id' => $data['update_id'] ?? null,
                'raw_payload' => $telegramMessage,
            ],
        ]);

        // 4. Update Conversation meta
        $conversation->increment('unread_count');
        $conversation->update([
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        // 5. Trigger Realtime event
        $this->dispatchRealtimeEvent($message);

        // 6. Check AI auto-reply
        $this->handleAiAutoReply($conversation, $text);

        return [
            'status' => 'success',
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ];
    }

    /**
     * Verify Bot Token and get bot profile.
     */
    public function getMe(string $botToken): ?array
    {
        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$botToken}/getMe");

            if ($response->successful() && $response->json('ok')) {
                return $response->json('result');
            }
        } catch (\Throwable $e) {
            Log::error('Telegram getMe error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Register Webhook URL with Telegram API.
     */
    public function setWebhook(string $botToken, string $webhookUrl, ?string $secretToken = null): bool
    {
        try {
            $params = ['url' => $webhookUrl];
            if ($secretToken) {
                $params['secret_token'] = $secretToken;
            }

            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/setWebhook", $params);

            return $response->successful() && $response->json('ok');
        } catch (\Throwable $e) {
            Log::error('Telegram setWebhook error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Gracefully dispatch realtime broadcast event.
     */
    protected function dispatchRealtimeEvent(Message $message): void
    {
        try {
            broadcast(new MessageSent($message->conversation, $message));
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed in Telegram message: '.$e->getMessage());
        }
    }
}
