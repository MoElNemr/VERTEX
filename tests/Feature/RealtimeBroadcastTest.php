<?php

namespace Tests\Feature;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Contact $contact;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'name' => 'Broadcast Test Store',
        ]);

        $this->contact = Contact::create([
            'business_id' => $this->business->id,
            'name' => 'Ahmed Client',
            'platform' => 'whatsapp',
            'platform_sender_id' => '201011112222',
        ]);

        $this->conversation = Conversation::create([
            'business_id' => $this->business->id,
            'contact_id' => $this->contact->id,
            'platform' => 'whatsapp',
            'status' => 'open',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);
    }

    public function test_message_sent_event_is_dispatched_when_agent_sends_reply(): void
    {
        Event::fake([MessageSent::class]);

        $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->postJson(route('inbox.send', $this->conversation->id), [
                'body' => 'مرحباً، تم تأكيد طلبك!',
            ])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        Event::assertDispatched(MessageSent::class, function ($event) {
            return $event->conversation->id === $this->conversation->id
                && $event->message->body === 'مرحباً، تم تأكيد طلبك!';
        });
    }

    public function test_conversation_updated_event_is_dispatched_on_status_change(): void
    {
        Event::fake([ConversationUpdated::class]);

        $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->patchJson(route('inbox.status', $this->conversation->id), [
                'status' => 'closed',
            ])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        Event::assertDispatched(ConversationUpdated::class, function ($event) {
            return $event->conversation->id === $this->conversation->id
                && $event->conversation->status === 'closed';
        });
    }

    public function test_conversation_updated_event_is_dispatched_on_agent_assignment(): void
    {
        $teamMember = TeamMember::create([
            'user_id' => $this->owner->id,
            'name' => 'Agent Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $this->business->teamMembers()->attach($teamMember->id, ['role' => 'agent']);

        Event::fake([ConversationUpdated::class]);

        $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->patchJson(route('inbox.assign', $this->conversation->id), [
                'assigned_to' => $teamMember->id,
            ])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        Event::assertDispatched(ConversationUpdated::class, function ($event) use ($teamMember) {
            return $event->conversation->id === $this->conversation->id
                && $event->conversation->assigned_to === $teamMember->id;
        });
    }

    public function test_message_sent_broadcast_channels_and_payload(): void
    {
        $message = $this->conversation->messages()->create([
            'sender_type' => 'agent',
            'body' => 'رسالة تجريبية',
            'type' => 'text',
            'status' => 'sent',
        ]);

        $event = new MessageSent($this->conversation, $message);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertEquals('private-business.' . $this->business->id, $channels[0]->name);
        $this->assertEquals('message.sent', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertEquals($this->conversation->id, $payload['conversation_id']);
        $this->assertEquals('رسالة تجريبية', $payload['message']['body']);
        $this->assertEquals($this->conversation->id, $payload['conversation']['id']);
    }
}
