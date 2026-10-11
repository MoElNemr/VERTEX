<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BusinessSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_switch_active_business(): void
    {
        $owner = User::factory()->create();
        $business1 = Business::create(['user_id' => $owner->id, 'name' => 'Store One']);
        $business2 = Business::create(['user_id' => $owner->id, 'name' => 'Store Two']);

        $response = $this->actingAs($owner, 'web')
            ->withSession(['current_business_id' => $business1->id])
            ->post(route('businesses.switch', $business2->id));

        $response->assertStatus(302);
        $this->assertEquals($business2->id, session('current_business_id'));
    }

    public function test_owner_cannot_switch_to_another_users_business(): void
    {
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();
        $business1 = Business::create(['user_id' => $owner1->id, 'name' => 'Store One']);
        $business2 = Business::create(['user_id' => $owner2->id, 'name' => 'Other Store']);

        $response = $this->actingAs($owner1, 'web')
            ->withSession(['current_business_id' => $business1->id])
            ->post(route('businesses.switch', $business2->id));

        $response->assertStatus(403);
        $this->assertEquals($business1->id, session('current_business_id'));
    }

    public function test_team_member_can_switch_to_assigned_business(): void
    {
        $owner = User::factory()->create();
        $business1 = Business::create(['user_id' => $owner->id, 'name' => 'Store One']);
        $business2 = Business::create(['user_id' => $owner->id, 'name' => 'Store Two']);

        $teamMember = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent Omar',
            'email' => 'omar@test.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $teamMember->businesses()->attach([$business1->id, $business2->id]);

        $response = $this->actingAs($teamMember, 'team')
            ->withSession(['current_business_id' => $business1->id])
            ->post(route('businesses.switch', $business2->id));

        $response->assertStatus(302);
        $this->assertEquals($business2->id, session('current_business_id'));
    }

    public function test_team_member_cannot_switch_to_unassigned_business(): void
    {
        $owner = User::factory()->create();
        $business1 = Business::create(['user_id' => $owner->id, 'name' => 'Store One']);
        $business2 = Business::create(['user_id' => $owner->id, 'name' => 'Store Two']);

        $teamMember = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent Omar',
            'email' => 'omar@test.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $teamMember->businesses()->attach($business1->id);

        $response = $this->actingAs($teamMember, 'team')
            ->withSession(['current_business_id' => $business1->id])
            ->post(route('businesses.switch', $business2->id));

        $response->assertStatus(403);
    }

    public function test_switching_from_channels_page_redirects_to_new_business_channels(): void
    {
        $owner = User::factory()->create();
        $business1 = Business::create(['user_id' => $owner->id, 'name' => 'Store One']);
        $business2 = Business::create(['user_id' => $owner->id, 'name' => 'Store Two']);

        $response = $this->actingAs($owner, 'web')
            ->withSession(['current_business_id' => $business1->id])
            ->from(route('channels.index', $business1->id))
            ->post(route('businesses.switch', $business2->id));

        $response->assertRedirect(route('channels.index', $business2->id));
        $this->assertEquals($business2->id, session('current_business_id'));
    }
}
