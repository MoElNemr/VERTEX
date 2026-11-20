<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAiSetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\MetaOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class PhaseZeroSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_is_throttled_after_consecutive_failed_attempts(): void
    {
        $admin = User::factory()->create([
            'email' => 'master@vertex.com',
            'password' => Hash::make('correct_pass'),
            'is_super_admin' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('admin.login.store'), [
                'email' => 'master@vertex.com',
                'password' => 'wrong_pass',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt should be throttled
        $response = $this->post(route('admin.login.store'), [
            'email' => 'master@vertex.com',
            'password' => 'wrong_pass',
        ]);

        $response->assertSessionHasErrors('email');
        $msg = session('errors')->first('email');
        $this->assertTrue(str_contains($msg, 'seconds') || str_contains($msg, 'ثانية') || str_contains($msg, 'Too many'));
    }

    public function test_team_login_is_throttled_after_consecutive_failed_attempts(): void
    {
        $owner = User::factory()->create();
        $team = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent Test',
            'email' => 'team_agent@vertex.com',
            'password' => 'secret123',
            'role' => 'agent',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('team.login.store'), [
                'email' => 'team_agent@vertex.com',
                'password' => 'wrong_pass',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt throttled
        $response = $this->post(route('team.login.store'), [
            'email' => 'team_agent@vertex.com',
            'password' => 'wrong_pass',
        ]);

        $response->assertSessionHasErrors('email');
        $msg = session('errors')->first('email');
        $this->assertTrue(str_contains($msg, 'seconds') || str_contains($msg, 'ثانية') || str_contains($msg, 'Too many'));
    }

    public function test_team_member_cannot_access_platform_without_owner_active_subscription(): void
    {
        $owner = User::factory()->create();
        $owner->subscriptions()->delete();
        // Owner has NO active subscription

        $business = Business::create([
            'user_id' => $owner->id,
            'name' => 'Owner Store',
        ]);

        $team = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Support Agent',
            'email' => 'agent_sub@vertex.com',
            'password' => 'password123',
            'role' => 'agent',
            'is_active' => true,
        ]);
        $team->businesses()->attach($business->id);

        $response = $this->actingAs($team, 'team')
            ->withSession(['current_business_id' => $business->id])
            ->get(route('inbox.index'));

        // Subscription middleware should abort 403 for team member whose owner has no subscription
        $response->assertStatus(403);
    }

    public function test_team_admin_cannot_escalate_role_or_assign_unauthorized_stores(): void
    {
        $owner = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Plan Pro',
            'type' => 'premium',
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $owner->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $store1 = Business::create(['user_id' => $owner->id, 'name' => 'Store 1']);
        $store2 = Business::create(['user_id' => $owner->id, 'name' => 'Store 2']);

        // Team admin only assigned to Store 1
        $admin = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Admin User',
            'email' => 'admin_test@vertex.com',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $admin->businesses()->attach($store1->id);

        $agent = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent User',
            'email' => 'agent_under@vertex.com',
            'password' => 'password123',
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent->businesses()->attach($store1->id);

        // 1. Team admin tries to create another admin -> 403
        $resCreate = $this->actingAs($admin, 'team')->post(route('team.store'), [
            'name' => 'Rogue Admin',
            'email' => 'rogue_admin@vertex.com',
            'password' => 'password123',
            'role' => 'admin',
            'business_ids' => [$store1->id],
        ]);
        $resCreate->assertStatus(403);

        // 2. Team admin tries to assign store2 (which they do not have access to) -> 403
        $resAssign = $this->actingAs($admin, 'team')->post(route('team.store'), [
            'name' => 'New Agent',
            'email' => 'new_agent@vertex.com',
            'password' => 'password123',
            'role' => 'agent',
            'business_ids' => [$store1->id, $store2->id],
        ]);
        $resAssign->assertStatus(403);

        // 3. Team admin tries to promote agent to admin -> 403
        $resPromote = $this->actingAs($admin, 'team')->put(route('team.update', $agent->id), [
            'name' => $agent->name,
            'email' => $agent->email,
            'role' => 'admin',
            'business_ids' => [$store1->id],
        ]);
        $resPromote->assertStatus(403);
    }

    public function test_ai_settings_cannot_be_accessed_by_team_agent(): void
    {
        $owner = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Plan Pro',
            'type' => 'premium',
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $owner->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $business = Business::create(['user_id' => $owner->id, 'name' => 'AI Store']);

        $agent = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Agent Role',
            'email' => 'agent_ai@vertex.com',
            'password' => 'password123',
            'role' => 'agent',
            'is_active' => true,
        ]);
        $agent->businesses()->attach($business->id);

        // Accessing AI settings should fail for role: agent
        $response = $this->actingAs($agent, 'team')->get(route('ai-settings.show', $business->id));
        $response->assertStatus(403);

        $responseUpdate = $this->actingAs($agent, 'team')->post(route('ai-settings.update', $business->id), [
            'ai_enabled' => true,
        ]);
        $responseUpdate->assertStatus(403);
    }

    public function test_ai_api_key_is_hidden_from_serialization(): void
    {
        $owner = User::factory()->create();
        $business = Business::create(['user_id' => $owner->id, 'name' => 'Secret AI Store']);

        $aiSetting = BusinessAiSetting::create([
            'business_id' => $business->id,
            'api_key' => 'gsk_secret_1234567890abcdef',
            'provider' => 'groq',
        ]);

        $serialized = $aiSetting->toArray();
        $this->assertArrayNotHasKey('api_key', $serialized);

        $json = json_encode($aiSetting);
        $this->assertStringNotContainsString('gsk_secret_1234567890abcdef', $json);
    }

    public function test_google_login_rejects_unverified_email(): void
    {
        $googleMock = Mockery::mock(GoogleAuthService::class);
        $googleMock->shouldReceive('isEnabled')
            ->andReturn(true);
        $googleMock->shouldReceive('getClientId')
            ->andReturn('google_client_id');
        $googleMock->shouldReceive('handleCallback')
            ->with('auth_code_123')
            ->once()
            ->andReturn([
                'id' => '12345',
                'email' => 'unverified@example.com',
                'name' => 'Unverified User',
                'avatar' => null,
                'email_verified' => false,
            ]);

        $this->app->instance(GoogleAuthService::class, $googleMock);

        session(['google_auth_state' => 'valid_state_123']);

        $response = $this->get(route('auth.google.callback', [
            'state' => 'valid_state_123',
            'code' => 'auth_code_123',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('غير موثق', session('error'));
    }

    public function test_super_admin_cannot_be_linked_or_logged_in_via_facebook(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin_super@vertex.com',
            'is_super_admin' => true,
        ]);

        $metaMock = Mockery::mock(MetaOAuthService::class);
        $metaMock->shouldReceive('exchangeCodeForToken')
            ->once()
            ->andReturn(['access_token' => 'fb_user_token_999']);
        $metaMock->shouldReceive('getUserProfile')
            ->once()
            ->andReturn([
                'id' => 'fb_admin_id_999',
                'name' => 'Admin Hacker',
                'email' => 'admin_super@vertex.com',
            ]);

        $this->app->instance(MetaOAuthService::class, $metaMock);

        session(['fb_auth_state' => 'fb_state_123']);

        $response = $this->get(route('auth.facebook.callback', [
            'state' => 'fb_state_123',
            'code' => 'valid_code',
        ]));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('web');
    }
}
