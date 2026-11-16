<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\AutoReplyRule;
use App\Models\Business;
use App\Models\BusinessAiSetting;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoReplyService
{
    public function __construct(
        protected MessageQuotaService $quotaService,
        protected AiService $aiService
    ) {}

    /**
     * Process an inbound direct message through the unified auto-reply pipeline.
     */
    public function handleInboundDirectMessage(Conversation $conversation, Message $inboundMessage): ?Message
    {
        $business = $conversation->business;
        $owner = $business?->user;

        if (! $owner) {
            return null;
        }

        // 1. Quota check
        if (! $this->quotaService->canSend($owner, 1)) {
            Log::info("Auto-reply skipped for business {$business->id}: message quota exhausted.");

            return null;
        }

        $platform = $conversation->platform;
        $text = trim((string) $inboundMessage->body);

        // 2. Priority 1: Welcome trigger (if contact's first inbound message)
        $inboundCount = $conversation->messages()->where('sender_type', 'contact')->count();
        if ($inboundCount <= 1) {
            $welcomeRule = AutoReplyRule::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->where('trigger_type', 'welcome')
                ->whereIn('platform', [$platform, 'all'])
                ->orderBy('priority')
                ->first();

            if ($welcomeRule) {
                return $this->dispatchBotReply($conversation, $welcomeRule->reply_text, "Welcome message rule: {$welcomeRule->name}");
            }
        }

        // 3. Priority 1 (b): Static Keyword Rules
        $rules = AutoReplyRule::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->where('trigger_type', 'keyword')
            ->whereIn('platform', [$platform, 'all'])
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesKeywords($text, (array) ($rule->keywords ?? []), $rule->match_type)) {
                return $this->dispatchBotReply($conversation, $rule->reply_text, "Keyword rule: {$rule->name}");
            }
        }

        // 4. Priority 2: AI Auto-Reply (Groq)
        $aiSetting = BusinessAiSetting::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        if ($aiSetting && $aiSetting->ai_enabled) {
            $allowedPlatforms = $aiSetting->auto_reply_platforms ?? ['whatsapp', 'facebook', 'instagram', 'telegram', 'tiktok'];
            if (in_array($platform, $allowedPlatforms, true)) {
                if (! $aiSetting->working_hours_only || $this->isWithinWorkingHours($aiSetting)) {
                    if (! empty($aiSetting->reply_delay_seconds) && $aiSetting->reply_delay_seconds > 0 && $aiSetting->reply_delay_seconds <= 3) {
                        sleep((int) $aiSetting->reply_delay_seconds);
                    }

                    $aiReplyText = $this->aiService->autoReply($conversation, $aiSetting, $text);
                    if ($aiReplyText) {
                        return $this->dispatchBotReply($conversation, $aiReplyText, 'AI Assistant auto-reply');
                    }
                }
            }
        }

        // 5. Priority 3: Fallback Rule (with 24h cooldown)
        $fallbackRule = AutoReplyRule::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->where('trigger_type', 'fallback')
            ->whereIn('platform', [$platform, 'all'])
            ->orderBy('priority')
            ->first();

        if ($fallbackRule) {
            $cacheKey = "auto_reply_fallback_{$conversation->id}";
            if (! Cache::has($cacheKey)) {
                Cache::put($cacheKey, true, now()->addHours(24));

                return $this->dispatchBotReply($conversation, $fallbackRule->reply_text, "Fallback rule: {$fallbackRule->name}");
            }
        }

        return null;
    }

    /**
     * Process an inbound comment and trigger comment reply + optional private DM.
     */
    public function handleInboundComment(
        Business $business,
        string $platform,
        string $commentId,
        string $commentText,
        string $commenterId,
        ?string $commenterName,
        callable $publicReplySender,
        ?callable $privateDmSender = null
    ): bool {
        $owner = $business->user;
        if (! $owner || ! $this->quotaService->canSend($owner, 1)) {
            Log::info("Comment auto-reply skipped for business {$business->id}: quota exhausted or no owner.");

            return false;
        }

        // Check comment rules
        $rules = AutoReplyRule::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->where('trigger_type', 'comment')
            ->whereIn('platform', [$platform, 'all'])
            ->orderBy('priority')
            ->get();

        $matchedRule = null;
        foreach ($rules as $rule) {
            $keywords = (array) ($rule->keywords ?? []);
            if (empty($keywords) || $this->matchesKeywords($commentText, $keywords, $rule->match_type)) {
                $matchedRule = $rule;
                break;
            }
        }

        if (! $matchedRule) {
            return false;
        }

        $replyText = $this->interpolate($matchedRule->reply_text, $commenterName, $platform);
        $publicSuccess = $publicReplySender($replyText);

        if ($publicSuccess) {
            $this->quotaService->consumeQuota(
                $owner,
                $business->id,
                $platform,
                1,
                "Comment auto-reply to {$commenterName}"
            );

            // Optional private DM
            if (! empty($matchedRule->private_dm_text) && $privateDmSender) {
                $dmText = $this->interpolate($matchedRule->private_dm_text, $commenterName, $platform);
                $privateDmSender($dmText);
            }

            return true;
        }

        return false;
    }

    /**
     * Dispatch and record a bot reply message to the conversation.
     */
    public function dispatchBotReply(Conversation $conversation, string $text, string $reason): Message
    {
        $contact = $conversation->contact;
        $interpolatedText = $this->interpolate($text, $contact?->name, $conversation->platform);

        $message = $conversation->messages()->create([
            'sender_type' => 'bot',
            'body' => $interpolatedText,
            'type' => 'text',
            'status' => 'sent',
            'metadata' => ['trigger_reason' => $reason],
        ]);

        $conversation->update([
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        // Send via ChannelManager
        try {
            app(ChannelManager::class)->sendMessage($conversation, $message);
        } catch (\Throwable $e) {
            Log::warning("Bot outbound message failed: {$e->getMessage()}");
        }

        // Broadcast to Inertia unified inbox
        try {
            broadcast(new MessageSent($conversation, $message));
        } catch (\Throwable $e) {
            Log::warning("Broadcast failed in dispatchBotReply: {$e->getMessage()}");
        }

        return $message;
    }

    /**
     * Check if text matches given keywords with match_type.
     *
     * @param  array<int, string>  $keywords
     */
    public function matchesKeywords(string $text, array $keywords, string $matchType): bool
    {
        if (empty($keywords)) {
            return true;
        }

        $normalizedText = mb_strtolower(trim($text), 'UTF-8');

        foreach ($keywords as $kw) {
            $normalizedKw = mb_strtolower(trim($kw), 'UTF-8');
            if ($normalizedKw === '') {
                continue;
            }

            if ($matchType === 'exact' && $normalizedText === $normalizedKw) {
                return true;
            }

            if ($matchType === 'starts_with' && str_starts_with($normalizedText, $normalizedKw)) {
                return true;
            }

            if ($matchType === 'contains' && str_contains($normalizedText, $normalizedKw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace placeholders {name} and {platform}.
     */
    protected function interpolate(string $text, ?string $name, string $platform): string
    {
        $platformLabels = [
            'whatsapp' => 'واتساب',
            'facebook' => 'فيسبوك',
            'instagram' => 'إنستجرام',
            'telegram' => 'تيليجرام',
            'tiktok' => 'تيك توك',
        ];

        return str_replace(
            ['{name}', '{platform}'],
            [$name ?: 'عميلنا العزيز', $platformLabels[$platform] ?? $platform],
            $text
        );
    }

    /**
     * Check working hours supporting overnight shift ranges.
     */
    protected function isWithinWorkingHours(BusinessAiSetting $setting): bool
    {
        $hour = now()->hour;
        $start = (int) ($setting->work_start_hour ?? 9);
        $end = (int) ($setting->work_end_hour ?? 18);

        if ($start <= $end) {
            return $hour >= $start && $hour < $end;
        }

        return $hour >= $start || $hour < $end;
    }
}
