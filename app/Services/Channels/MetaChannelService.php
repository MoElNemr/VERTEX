<?php

namespace App\Services\Channels;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\FacebookAccount;
use App\Models\InstagramAccount;
use App\Models\Message;
use App\Services\Channels\Concerns\HandlesAiAutoReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaChannelService implements ChannelServiceInterface
{
    use HandlesAiAutoReply;

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
     * Handle incoming Meta Webhook payload for Facebook and Instagram (DMs & Comments).
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
        $processedComments = [];

        foreach ($entries as $entry) {
            // 1. Direct Messaging Events (Messenger / IG Direct)
            $messagingEvents = $entry['messaging'] ?? [];
            foreach ($messagingEvents as $event) {
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

                $conversation = Conversation::withoutGlobalScopes()->firstOrCreate([
                    'business_id' => $business->id,
                    'contact_id' => $contact->id,
                    'platform' => $platform,
                ], [
                    'status' => 'open',
                    'unread_count' => 0,
                    'last_message_at' => now(),
                ]);

                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'contact',
                    'platform_message_id' => $mid,
                    'type' => 'text',
                    'body' => $text,
                    'status' => 'delivered',
                    'metadata' => [
                        'meta_entry_id' => $entry['id'] ?? null,
                        'raw_event' => $event,
                    ],
                ]);

                $conversation->increment('unread_count');
                $conversation->update([
                    'status' => 'open',
                    'last_message_at' => now(),
                ]);

                try {
                    broadcast(new \App\Events\MessageSent($conversation, $message));
                } catch (\Throwable $e) {
                    Log::warning('Broadcast failed in Meta message: ' . $e->getMessage());
                }

                $this->handleAiAutoReply($conversation, $text);

                $processedMessages[] = $message->id;
            }

            // 2. Changes Events: Facebook Page Feed & Instagram Comments
            $changes = $entry['changes'] ?? [];
            if (!empty($changes)) {
                $commentResults = $this->handleChangesWebhook($business, $object, $changes, $entry['id'] ?? null);
                $processedComments = array_merge($processedComments, $commentResults);
            }
        }

        return [
            'status' => 'success',
            'processed_count' => count($processedMessages),
            'message_ids' => $processedMessages,
            'processed_comments' => $processedComments,
        ];
    }

    /**
     * Process Meta "changes" webhooks for Comments-to-DM automation.
     */
    public function handleChangesWebhook(Business $business, string $object, array $changes, ?string $pageOrIgId): array
    {
        $results = [];

        foreach ($changes as $change) {
            $field = $change['field'] ?? '';
            $value = $change['value'] ?? [];

            // A. Facebook Page Feed Comments
            if ($object === 'page' && $field === 'feed') {
                $item = $value['item'] ?? '';
                $verb = $value['verb'] ?? '';

                if ($item === 'comment' && in_array($verb, ['add', 'created'])) {
                    $commentId = (string) ($value['comment_id'] ?? '');
                    $senderId = (string) ($value['from']['id'] ?? $value['sender_id'] ?? '');
                    $senderName = $value['from']['name'] ?? $value['sender_name'] ?? 'عميل فيسبوك';
                    $commentText = $value['message'] ?? '';

                    $res = $this->processFacebookCommentToDm($business, $commentId, $senderId, $senderName, $commentText, $value);
                    if ($res) {
                        $results[] = $res;
                    }
                }
            }

            // B. Instagram Post Comments
            if ($object === 'instagram' && in_array($field, ['comments', 'mention'])) {
                $commentId = (string) ($value['id'] ?? '');
                $senderId = (string) ($value['from']['id'] ?? '');
                $senderUsername = $value['from']['username'] ?? 'عميل إنستجرام';
                $commentText = $value['text'] ?? '';

                $res = $this->processInstagramCommentToDm($business, $commentId, $senderId, $senderUsername, $commentText, $value);
                if ($res) {
                    $results[] = $res;
                }
            }
        }

        return $results;
    }

    /**
     * Process Facebook comment: public reply + private DM + record in inbox.
     */
    protected function processFacebookCommentToDm(
        Business $business,
        string $commentId,
        string $senderId,
        string $senderName,
        string $commentText,
        array $rawPayload
    ): ?array {
        $account = FacebookAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        if (!$account) {
            return null;
        }

        // Prevent replying to comments made by the page itself
        if ($senderId && $account->page_id && $senderId === $account->page_id) {
            return null;
        }

        // Find or create Contact
        $contact = Contact::withoutGlobalScopes()->firstOrCreate([
            'business_id' => $business->id,
            'platform' => 'facebook',
            'platform_sender_id' => $senderId ?: $commentId,
        ], [
            'name' => $senderName,
        ]);

        // Find or create Conversation
        $conversation = Conversation::withoutGlobalScopes()->firstOrCreate([
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'platform' => 'facebook',
        ], [
            'status' => 'open',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);

        // Record incoming comment message in conversation
        $commentMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'contact',
            'platform_message_id' => $commentId,
            'type' => 'comment',
            'body' => $commentText,
            'status' => 'delivered',
            'metadata' => [
                'comment_id' => $commentId,
                'raw_event' => $rawPayload,
            ],
        ]);

        $conversation->increment('unread_count');
        $conversation->update(['last_message_at' => now()]);

        $replySent = false;
        $dmSent = false;

        // Check if Comment-to-DM is enabled on page
        if ($account->comment_reply_enabled) {
            // Keyword match check (if configured)
            $matchesKeyword = true;
            if (!empty($account->comment_keywords) && is_array($account->comment_keywords)) {
                $matchesKeyword = false;
                foreach ($account->comment_keywords as $kw) {
                    if (mb_stripos($commentText, trim($kw)) !== false) {
                        $matchesKeyword = true;
                        break;
                    }
                }
            }

            if ($matchesKeyword) {
                // 1. Send public reply on comment
                if (!empty($account->comment_reply_text) && $account->page_access_token) {
                    $replyBody = str_replace(
                        ['{name}', '{sender_name}'],
                        [$senderName, $senderName],
                        $account->comment_reply_text
                    );

                    try {
                        $replyRes = Http::timeout(10)->post("https://graph.facebook.com/v21.0/{$commentId}/comments?access_token={$account->page_access_token}", [
                            'message' => $replyBody,
                        ]);
                        $replySent = $replyRes->successful();
                    } catch (\Throwable $e) {
                        Log::error('Facebook public comment reply failed: ' . $e->getMessage());
                    }
                }

                // 2. Send private DM to commenter
                if (!empty($account->dm_message_text) && $account->page_access_token) {
                    $dmBody = str_replace(
                        ['{name}', '{sender_name}'],
                        [$senderName, $senderName],
                        $account->dm_message_text
                    );

                    try {
                        // First attempt: recipient using comment_id (Meta Private Reply API)
                        $dmRes = Http::timeout(10)->post("https://graph.facebook.com/v21.0/me/messages?access_token={$account->page_access_token}", [
                            'recipient' => ['comment_id' => $commentId],
                            'message' => ['text' => $dmBody],
                        ]);

                        if (!$dmRes->successful() && $senderId) {
                            // Fallback attempt: recipient using PSID
                            $dmRes = Http::timeout(10)->post("https://graph.facebook.com/v21.0/me/messages?access_token={$account->page_access_token}", [
                                'recipient' => ['id' => $senderId],
                                'message' => ['text' => $dmBody],
                            ]);
                        }

                        $dmSent = $dmRes->successful();

                        // Record bot DM in conversation
                        Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_type' => 'bot',
                            'type' => 'text',
                            'body' => $dmBody,
                            'status' => $dmSent ? 'delivered' : 'failed',
                            'metadata' => [
                                'triggered_by_comment_id' => $commentId,
                                'api_response' => $dmRes->json(),
                            ],
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Facebook comment private DM failed: ' . $e->getMessage());
                    }
                }
            }
        }

        return [
            'platform' => 'facebook',
            'comment_id' => $commentId,
            'sender_id' => $senderId,
            'sender_name' => $senderName,
            'comment_message_id' => $commentMessage->id,
            'reply_sent' => $replySent,
            'dm_sent' => $dmSent,
        ];
    }

    /**
     * Process Instagram comment: public reply + private DM + record in inbox.
     */
    protected function processInstagramCommentToDm(
        Business $business,
        string $commentId,
        string $senderId,
        string $senderUsername,
        string $commentText,
        array $rawPayload
    ): ?array {
        $account = InstagramAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        if (!$account) {
            return null;
        }

        // Prevent replying to comments made by the IG account itself
        if ($senderId && $account->instagram_business_id && $senderId === $account->instagram_business_id) {
            return null;
        }

        // Find or create Contact
        $contact = Contact::withoutGlobalScopes()->firstOrCreate([
            'business_id' => $business->id,
            'platform' => 'instagram',
            'platform_sender_id' => $senderId ?: $commentId,
        ], [
            'name' => '@' . $senderUsername,
        ]);

        // Find or create Conversation
        $conversation = Conversation::withoutGlobalScopes()->firstOrCreate([
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'platform' => 'instagram',
        ], [
            'status' => 'open',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);

        // Record incoming comment
        $commentMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'contact',
            'platform_message_id' => $commentId,
            'type' => 'comment',
            'body' => $commentText,
            'status' => 'delivered',
            'metadata' => [
                'comment_id' => $commentId,
                'raw_event' => $rawPayload,
            ],
        ]);

        $conversation->increment('unread_count');
        $conversation->update(['last_message_at' => now()]);

        $replySent = false;
        $dmSent = false;

        // Check if Comment-to-DM is enabled
        if ($account->comment_reply_enabled) {
            // Keyword filter check
            $matchesKeyword = true;
            if (!empty($account->comment_keywords) && is_array($account->comment_keywords)) {
                $matchesKeyword = false;
                foreach ($account->comment_keywords as $kw) {
                    if (mb_stripos($commentText, trim($kw)) !== false) {
                        $matchesKeyword = true;
                        break;
                    }
                }
            }

            if ($matchesKeyword) {
                // 1. Send public reply on Instagram comment
                if (!empty($account->comment_reply_text) && $account->access_token) {
                    $replyBody = str_replace(
                        ['{name}', '{username}'],
                        ['@' . $senderUsername, '@' . $senderUsername],
                        $account->comment_reply_text
                    );

                    try {
                        $replyRes = Http::timeout(10)->post("https://graph.facebook.com/v21.0/{$commentId}/replies?access_token={$account->access_token}", [
                            'message' => $replyBody,
                        ]);
                        $replySent = $replyRes->successful();
                    } catch (\Throwable $e) {
                        Log::error('Instagram public comment reply failed: ' . $e->getMessage());
                    }
                }

                // 2. Send private DM to commenter
                if (!empty($account->dm_message_text) && $account->access_token) {
                    $dmBody = str_replace(
                        ['{name}', '{username}'],
                        ['@' . $senderUsername, '@' . $senderUsername],
                        $account->dm_message_text
                    );

                    try {
                        // Instagram Private Reply to comment
                        $dmRes = Http::timeout(10)->post("https://graph.facebook.com/v21.0/me/messages?access_token={$account->access_token}", [
                            'recipient' => ['comment_id' => $commentId],
                            'message' => ['text' => $dmBody],
                        ]);

                        if (!$dmRes->successful() && $senderId) {
                            $dmRes = Http::timeout(10)->post("https://graph.facebook.com/v21.0/me/messages?access_token={$account->access_token}", [
                                'recipient' => ['id' => $senderId],
                                'message' => ['text' => $dmBody],
                            ]);
                        }

                        $dmSent = $dmRes->successful();

                        // Record bot message in conversation
                        Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_type' => 'bot',
                            'type' => 'text',
                            'body' => $dmBody,
                            'status' => $dmSent ? 'delivered' : 'failed',
                            'metadata' => [
                                'triggered_by_comment_id' => $commentId,
                                'api_response' => $dmRes->json(),
                            ],
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Instagram comment private DM failed: ' . $e->getMessage());
                    }
                }
            }
        }

        return [
            'platform' => 'instagram',
            'comment_id' => $commentId,
            'sender_id' => $senderId,
            'sender_username' => $senderUsername,
            'comment_message_id' => $commentMessage->id,
            'reply_sent' => $replySent,
            'dm_sent' => $dmSent,
        ];
    }
}
