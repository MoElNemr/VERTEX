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
            if (!$aiSetting->working_hours_only || $this->isWithinWorkingHours($aiSetting)) {
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
                        Log::warning('AI auto-reply outbound send error: ' . $e->getMessage());
                    }

                    try {
                        broadcast(new MessageSent($conversation, $replyMsg));
                    } catch (\Throwable $e) {
                        Log::warning('Broadcast failed in AI auto-reply: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * التحقق مما إذا كان الوقت الحالي ضمن ساعات العمل المحددة
     */
    protected function isWithinWorkingHours(BusinessAiSetting $setting): bool
    {
        $hour = now()->hour;
        $start = $setting->work_start_hour ?? 9;
        $end = $setting->work_end_hour ?? 18;
        return $hour >= $start && $hour < $end;
    }
}
