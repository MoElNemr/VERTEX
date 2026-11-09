<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * حدث بث تحديث بيانات المحادثة (الحالة، تعيين الوكيل، إلخ)
 */
class ConversationUpdated implements NotTenantAware, ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->conversation->business_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => $this->conversation->load(['contact', 'assignedAgent:id,name,role', 'latestMessage'])->toArray(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }
}
