<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * حدث بث إرسال أو استقبال رسالة جديدة في المحادثة عبر WebSockets
 */
class MessageSent implements ShouldBroadcastNow, NotTenantAware
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public Message $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.' . $this->conversation->business_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'message' => $this->message->load('senderAgent:id,name')->toArray(),
            'conversation' => [
                'id' => $this->conversation->id,
                'status' => $this->conversation->status,
                'last_message_at' => $this->conversation->last_message_at,
                'unread_count' => $this->conversation->unread_count,
                'contact' => $this->conversation->contact,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
