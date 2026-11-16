<?php

namespace App\Services\Channels\Concerns;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AutoReplyService;

/**
 * Trait to handle automated auto-reply pipelines across all communication channels.
 */
trait HandlesAiAutoReply
{
    /**
     * Dispatch incoming message through the unified auto-reply pipeline.
     */
    protected function handleAiAutoReply(Conversation $conversation, string $messageText, ?Message $inboundMessage = null): void
    {
        if (! $inboundMessage) {
            $inboundMessage = $conversation->messages()
                ->where('sender_type', 'contact')
                ->latest()
                ->first();
        }

        if (! $inboundMessage) {
            return;
        }

        app(AutoReplyService::class)->handleInboundDirectMessage($conversation, $inboundMessage);
    }
}
