<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_team_management_page(): void
    {
        $owner = User::factory()->create();
        $business = Business::create(['user_id' => $owner->id, 'name' => 'Store A']);

        $response = $this->actingAs($owner, 'web')
            ->withSession(['current_business_id' => $business->id])
            ->get(route('team.index'));

        $response->assertOk();
    }

    public function test_owner_can_create_team_member_with_store_scoping(): void
    {
        $owner = User::factory()->create();
        $business = Business::create(['user_id' => $owner->id, 'name' => 'Store A']);

        $response = $this->actingAs($owner, 'web')
            ->post(route('team.store'), [
                'name' => 'Ali Agent',
                'email' => 'ali@example.com',
                'password' => 'secret1234',
                'role' => 'agent',
                'business_ids' => [$business->id],
            ]);

        $response->assertRedirect(route('team.index'));
        $this->assertDatabaseHas('team_members', [
            'email' => 'ali@example.com',
            'role' => 'agent',
            'user_id' => $owner->id,
            'is_active' => true,
        ]);

        $member = TeamMember::where('email', 'ali@example.com')->first();
        $this->assertTrue($member->businesses->contains($business));
    }

    public function test_tenant_isolation_prevents_linking_other_owners_stores(): void
    {
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();
        $businessOfOwner2 = Business::create(['user_id' => $owner2->id, 'name' => 'Foreign Store']);

        // Owner 1 attempts to assign owner 2's store
        $response = $this->actingAs($owner1, 'web')
            ->post(route('team.store'), [
                'name' => 'Hacker Agent',
                'email' => 'hacker@example.com',
                'password' => 'secret1234',
                'role' => 'agent',
                'business_ids' => [$businessOfOwner2->id],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('team_members', ['email' => 'hacker@example.com']);
    }

    public function test_team_member_can_login_via_team_portal(): void
    {
        $owner = User::factory()->create();
        $business = Business::create(['user_id' => $owner->id, 'name' => 'Store A']);

        $member = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Sara Admin',
            'email' => 'sara@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $member->businesses()->attach($business->id);

        $response = $this->post(route('team.login'), [
            'email' => 'sara@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('inbox.index'));
        $this->assertAuthenticatedAs($member, 'team');
    }

    public function test_inactive_member_cannot_login(): void
    {
        $owner = User::factory()->create();
        $member = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Disabled Member',
            'email' => 'disabled@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => false,
        ]);

        $response = $this->post(route('team.login'), [
            'email' => 'disabled@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('team');
    }

    public function test_agent_is_isolated_to_assigned_stores_only(): void
    {
        $owner = User::factory()->create();
        $store1 = Business::create(['user_id' => $owner->id, 'name' => 'Store 1']);
        $store2 = Business::create(['user_id' => $owner->id, 'name' => 'Store 2']);

        $agent = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent Isolated',
            'email' => 'isolated@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        // Only linked to store 1
        $agent->businesses()->attach($store1->id);

        // Attempt to switch to store 2
        $response = $this->actingAs($agent, 'team')
            ->post(route('businesses.switch', $store2->id));

        $response->assertForbidden();
    }

    public function test_agent_sees_only_assigned_conversations_in_inbox(): void
    {
        $owner = User::factory()->create();
        $store = Business::create(['user_id' => $owner->id, 'name' => 'Store 1']);

        $agent1 = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent 1',
            'email' => 'agent1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent1->businesses()->attach($store->id);

        $contact = Contact::create([
            'business_id' => $store->id,
            'platform' => 'whatsapp',
            'platform_sender_id' => '12345',
            'name' => 'Customer',
        ]);

        // Conversation assigned to agent 1
        $convAssigned = Conversation::create([
            'business_id' => $store->id,
            'contact_id' => $contact->id,
            'platform' => 'whatsapp',
            'status' => 'open',
            'assigned_to' => $agent1->id,
        ]);

        $contact2 = Contact::create([
            'business_id' => $store->id,
            'platform' => 'telegram',
            'platform_sender_id' => '67890',
            'name' => 'Customer 2',
        ]);

        $agent2 = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent 2',
            'email' => 'agent2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent2->businesses()->attach($store->id);

        // Conversation assigned to someone else (agent 2)
        $convOther = Conversation::create([
            'business_id' => $store->id,
            'contact_id' => $contact2->id,
            'platform' => 'telegram',
            'status' => 'open',
            'assigned_to' => $agent2->id,
        ]);

        // Agent 1 can view their assigned conversation
        $this->actingAs($agent1, 'team')
            ->withSession(['current_business_id' => $store->id])
            ->get(route('inbox.messages', $convAssigned->id))
            ->assertOk();

        // Agent 1 CANNOT view someone else's conversation
        $this->actingAs($agent1, 'team')
            ->withSession(['current_business_id' => $store->id])
            ->get(route('inbox.messages', $convOther->id))
            ->assertForbidden();
    }

    public function test_supervisor_or_admin_can_assign_agent_to_conversation(): void
    {
        $owner = User::factory()->create();
        $store = Business::create(['user_id' => $owner->id, 'name' => 'Store 1']);

        $supervisor = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Supervisor Sam',
            'email' => 'sam@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supervisor',
            'is_active' => true,
        ]);
        $supervisor->businesses()->attach($store->id);

        $agent = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent Alex',
            'email' => 'alex@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent->businesses()->attach($store->id);

        $contact = Contact::create([
            'business_id' => $store->id,
            'platform' => 'telegram',
            'platform_sender_id' => '999',
            'name' => 'Client',
        ]);

        $conversation = Conversation::create([
            'business_id' => $store->id,
            'contact_id' => $contact->id,
            'platform' => 'telegram',
            'status' => 'open',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($supervisor, 'team')
            ->withSession(['current_business_id' => $store->id])
            ->patch(route('inbox.assign', $conversation->id), [
                'assigned_to' => $agent->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'assigned_to' => $agent->id,
        ]);
    }

    public function test_owner_can_toggle_and_delete_member(): void
    {
        $owner = User::factory()->create();
        $store = Business::create(['user_id' => $owner->id, 'name' => 'Store 1']);

        $member = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Member to Toggle',
            'email' => 'toggle@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $member->businesses()->attach($store->id);

        // Toggle status
        $this->actingAs($owner, 'web')
            ->patch(route('team.toggle-status', $member->id))
            ->assertRedirect(route('team.index'));

        $this->assertFalse($member->fresh()->is_active);

        // Delete member
        $this->actingAs($owner, 'web')
            ->delete(route('team.destroy', $member->id))
            ->assertRedirect(route('team.index'));

        $this->assertDatabaseMissing('team_members', ['id' => $member->id]);
    }
}
