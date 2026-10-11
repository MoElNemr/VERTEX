<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InboxFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $businessA;
    protected Business $businessB;
    protected TeamMember $agentA;
    protected TeamMember $agentB;
    protected Contact $contactA;
    protected Conversation $conversationA;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Unlimited',
            'type' => 'premium',
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create();
        Subscription::create([
            'user_id' => $this->owner->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $this->businessA = Business::create([
            'user_id' => $this->owner->id,
            'name' => 'Store Alpha',
        ]);

        $this->businessB = Business::create([
            'user_id' => $this->owner->id,
            'name' => 'Store Beta',
        ]);

        $this->agentA = TeamMember::create([
            'user_id' => $this->owner->id,
            'name' => 'Agent One',
            'email' => 'agent1@alpha.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $this->agentA->businesses()->attach($this->businessA->id);

        $this->agentB = TeamMember::create([
            'user_id' => $this->owner->id,
            'name' => 'Agent Two',
            'email' => 'agent2@beta.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $this->agentB->businesses()->attach($this->businessB->id);

        $this->contactA = Contact::create([
            'business_id' => $this->businessA->id,
            'name' => 'Test Customer',
            'phone' => '+201011112222',
            'platform' => 'whatsapp',
            'platform_sender_id' => '+201011112222',
        ]);

        $this->conversationA = Conversation::create([
            'business_id' => $this->businessA->id,
            'contact_id' => $this->contactA->id,
            'platform' => 'whatsapp',
            'status' => 'open',
            'unread_count' => 3,
            'assigned_to' => $this->agentA->id,
        ]);

        Message::create([
            'conversation_id' => $this->conversationA->id,
            'body' => 'Hello there',
            'sender_type' => 'contact',
            'type' => 'text',
            'status' => 'delivered',
        ]);
    }

    public function test_owner_with_no_businesses_is_redirected_to_businesses_index(): void
    {
        $userWithoutBusiness = User::factory()->create();

        $res = $this->actingAs($userWithoutBusiness)->get(route('inbox.index'));
        $res->assertRedirect(route('businesses.index'));
    }

    public function test_owner_with_businesses_auto_selects_first_business_or_uses_session(): void
    {
        // When session is empty, SetCurrentBusiness selects first business automatically
        $res = $this->actingAs($this->owner)->get(route('inbox.index'));
        $res->assertOk();
        $this->assertEquals($this->businessA->id, session('current_business_id'));

        // With explicit session for business B
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessB->id])
            ->get(route('inbox.index'));
        $res->assertOk();
        $this->assertEquals($this->businessB->id, session('current_business_id'));
    }

    public function test_agent_only_sees_assigned_conversations(): void
    {
        // Unassigned conversation
        $contact2 = Contact::create([
            'business_id' => $this->businessA->id,
            'name' => 'Customer Two',
            'platform' => 'whatsapp',
            'platform_sender_id' => 'cust2',
        ]);
        Conversation::create([
            'business_id' => $this->businessA->id,
            'contact_id' => $contact2->id,
            'platform' => 'whatsapp',
            'status' => 'open',
            'assigned_to' => null,
        ]);

        $res = $this->actingAs($this->agentA, 'team')
            ->withSession(['current_business_id' => $this->businessA->id])
            ->get(route('inbox.index'));

        $res->assertOk();
        $conversations = $res->viewData('page')['props']['conversations'];
        $this->assertCount(1, $conversations);
        $this->assertEquals($this->conversationA->id, $conversations[0]['id']);
    }

    public function test_fetching_messages_resets_unread_count(): void
    {
        $this->assertEquals(3, $this->conversationA->fresh()->unread_count);

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->getJson(route('inbox.messages', $this->conversationA->id));

        $res->assertOk();
        $this->assertCount(1, $res->json('messages'));
        $this->assertEquals(0, $this->conversationA->fresh()->unread_count);
    }

    public function test_send_message_updates_conversation_and_status(): void
    {
        $this->conversationA->update(['status' => 'closed']);

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->postJson(route('inbox.send', $this->conversationA->id), [
                'body' => 'Thank you for contacting us!',
            ]);

        $res->assertOk();
        $res->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversationA->id,
            'body' => 'Thank you for contacting us!',
            'sender_type' => 'agent',
        ]);

        // Auto reopened
        $this->assertEquals('open', $this->conversationA->fresh()->status);
    }

    public function test_update_conversation_status(): void
    {
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->patchJson(route('inbox.status', $this->conversationA->id), [
                'status' => 'closed',
            ]);

        $res->assertOk();
        $this->assertEquals('closed', $this->conversationA->fresh()->status);
    }

    public function test_assign_agent_validates_store_membership(): void
    {
        // Try assigning Agent B (who only belongs to Store Beta) to Store Alpha's conversation -> 422
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->patchJson(route('inbox.assign', $this->conversationA->id), [
                'assigned_to' => $this->agentB->id,
            ]);

        $res->assertStatus(422);

        // Assign valid Agent A
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->patchJson(route('inbox.assign', $this->conversationA->id), [
                'assigned_to' => $this->agentA->id,
            ]);

        $res->assertOk();
        $this->assertEquals($this->agentA->id, $this->conversationA->fresh()->assigned_to);

        // Unassign (null)
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->patchJson(route('inbox.assign', $this->conversationA->id), [
                'assigned_to' => null,
            ]);

        $res->assertOk();
        $this->assertNull($this->conversationA->fresh()->assigned_to);
    }

    public function test_agent_cannot_reassign_conversation(): void
    {
        $res = $this->actingAs($this->agentA, 'team')
            ->withSession(['current_business_id' => $this->businessA->id])
            ->patchJson(route('inbox.assign', $this->conversationA->id), [
                'assigned_to' => null,
            ]);

        $res->assertStatus(403);
    }

    public function test_transfer_conversation_to_another_store_of_same_owner(): void
    {
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->postJson(route('inbox.transfer', $this->conversationA->id), [
                'target_business_id' => $this->businessB->id,
            ]);

        $res->assertOk();
        $res->assertJsonPath('status', 'success');

        // Check conversation transferred to Store B
        $this->assertEquals($this->businessB->id, $this->conversationA->fresh()->business_id);
    }

    public function test_transfer_conversation_forbidden_to_foreign_store(): void
    {
        $otherUser = User::factory()->create();
        $foreignBusiness = Business::create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign Store',
        ]);

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->postJson(route('inbox.transfer', $this->conversationA->id), [
                'target_business_id' => $foreignBusiness->id,
            ]);

        $res->assertStatus(403);
    }
}
