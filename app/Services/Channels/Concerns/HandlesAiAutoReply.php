<?php

namespace App\Services\Channels\Concerns;

use App\Events\MessageSent;
use App\Models\BusinessAiSetting;
use App\Models\Conversation;
use App\Services\AiService;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Log;

/**
 * سمة لمعالجة الرد التلقائي عبر الذكاء الاصطناعي عند وصول رسالة جديدة
 */
trait HandlesAiAutoReply
{
    /**
     * التحقق من إعدادات الذكاء الاصطناعي وإرسال الرد التلقائي إن أمكن
     */
    protected function handleAiAutoReply(Conversation $conversation, string $messageText): void
    {
        $aiSetting = BusinessAiSetting::withoutGlobalScopes()
            ->where('business_id', $conversation->business_id)
            ->first();

        if ($aiSetting && $aiSetting->ai_enabled) {
            // Check allowed platforms (BUG-12)
            $allowedPlatforms = $aiSetting->auto_reply_platforms ?? ['whatsapp', 'facebook', 'instagram', 'telegram'];
            if (! in_array($conversation->platform, $allowedPlatforms, true)) {
                return;
            }

            if (! $aiSetting->working_hours_only || $this->isWithinWorkingHours($aiSetting)) {
                // Apply optional response delay if configured
                if (! empty($aiSetting->reply_delay_seconds) && $aiSetting->reply_delay_seconds > 0 && $aiSetting->reply_delay_seconds <= 3) {
                    sleep((int) $aiSetting->reply_delay_seconds);
                }

                $reply = app(AiService::class)->autoReply($conversation, $aiSetting, $messageText);
                if ($reply) {
                    $replyMsg = $conversation->messages()->create([
                        'sender_type' => 'agent',
                        'sender_id' => null,
                        'body' => $reply,
                        'type' => 'text',
                        'status' => 'sent',
                    ]);

                    try {
                        app(ChannelManager::class)->sendMessage($conversation, $replyMsg);
                    } catch (\Throwable $e) {
                        Log::warning('AI auto-reply outbound send error: '.$e->getMessage());
                    }

                    try {
                        broadcast(new MessageSent($conversation, $replyMsg));
                    } catch (\Throwable $e) {
                        Log::warning('Broadcast failed in AI auto-reply: '.$e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * التحقق مما إذا كان الوقت الحالي ضمن ساعات العمل المحددة (يدعم الورديات الليلية)
     */
    protected function isWithinWorkingHours(BusinessAiSetting $setting): bool
    {
        $hour = now()->hour;
        $start = (int) ($setting->work_start_hour ?? 9);
        $end = (int) ($setting->work_end_hour ?? 18);

        if ($start <= $end) {
            return $hour >= $start && $hour < $end;
        }

        // Crosses midnight (e.g. 18:00 to 02:00)
        return $hour >= $start || $hour < $end;
    }
}
