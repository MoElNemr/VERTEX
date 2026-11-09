<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChannelManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Business $business;

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

        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'name' => 'Store Tech',
        ]);
    }

    public function test_owner_can_view_channels_page(): void
    {
        $res = $this->actingAs($this->owner)
            ->get(route('channels.index', $this->business->id));

        $res->assertOk();
    }

    public function test_unauthorized_user_cannot_access_channels_page(): void
    {
        $otherUser = User::factory()->create();

        $res = $this->actingAs($otherUser)
            ->get(route('channels.index', $this->business->id));

        $res->assertStatus(403);
    }

    public function test_connect_telegram_with_valid_token(): void
    {
        Http::fake([
            'https://api.telegram.org/bot123456:ABC-DEF/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'username' => 'vertex_test_bot',
                ],
            ], 200),
            'https://api.telegram.org/bot123456:ABC-DEF/setWebhook' => Http::response([
                'ok' => true,
            ], 200),
        ]);

        $res = $this->actingAs($this->owner)
            ->post(route('channels.telegram.connect', $this->business->id), [
                'bot_token' => '123456:ABC-DEF',
            ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('telegram_accounts', [
            'business_id' => $this->business->id,
            'bot_username' => 'vertex_test_bot',
            'status' => 'connected',
        ]);
    }

    public function test_connect_facebook_page(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/page_123*' => Http::response([
                'id' => 'page_123',
                'name' => 'Vertex Official Page',
            ], 200),
            'https://graph.facebook.com/v21.0/page_123/subscribed_apps*' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $res = $this->actingAs($this->owner)
            ->post(route('channels.facebook.connect', $this->business->id), [
                'page_id' => 'page_123',
                'page_access_token' => 'EAAB_token',
            ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('facebook_accounts', [
            'business_id' => $this->business->id,
            'page_id' => 'page_123',
            'page_name' => 'Vertex Official Page',
            'status' => 'connected',
        ]);
    }

    public function test_connect_instagram_account(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/ig_456*' => Http::response([
                'id' => 'ig_456',
                'username' => 'vertex_store_ig',
            ], 200),
        ]);

        $res = $this->actingAs($this->owner)
            ->post(route('channels.instagram.connect', $this->business->id), [
                'instagram_business_id' => 'ig_456',
                'access_token' => 'IG_token',
            ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('instagram_accounts', [
            'business_id' => $this->business->id,
            'instagram_business_id' => 'ig_456',
            'username' => 'vertex_store_ig',
            'status' => 'connected',
        ]);
    }

    public function test_disconnect_channel(): void
    {
        $this->business->telegramAccount()->create([
            'bot_username' => 'vertex_test_bot',
            'bot_token' => 'tok',
            'status' => 'connected',
        ]);

        $res = $this->actingAs($this->owner)
            ->delete(route('channels.disconnect', ['business' => $this->business->id, 'channel' => 'telegram']));

        $res->assertRedirect();
        $this->assertDatabaseMissing('telegram_accounts', [
            'business_id' => $this->business->id,
        ]);
    }
}
