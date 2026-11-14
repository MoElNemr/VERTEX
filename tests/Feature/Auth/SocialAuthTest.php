<?php

namespace Tests\Feature\Auth;

use App\Models\Plan;
use App\Models\User;
use App\Services\MetaOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_facebook_sets_state_and_redirects(): void
    {
        $response = $this->get(route('auth.facebook.redirect'));

        $response->assertRedirect();
        $this->assertTrue(session()->has('fb_auth_state'));
        $targetUrl = $response->headers->get('Location');
        $this->assertStringContainsString('facebook.com', $targetUrl);
        $this->assertStringContainsString('public_profile', $targetUrl);
    }

    public function test_callback_with_state_mismatch_fails(): void
    {
        session(['fb_auth_state' => 'expected_state_123']);

        $response = $this->get(route('auth.facebook.callback', [
            'code' => 'fake_code',
            'state' => 'wrong_state_456',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest('web');
    }

    public function test_callback_creates_new_user_and_logs_in(): void
    {
        Plan::create([
            'name' => 'Standard Plan',
            'type' => 'standard',
            'price_monthly' => 199,
            'price_yearly' => 1990,
            'max_businesses' => 1,
            'is_active' => true,
        ]);

        $mockMetaService = Mockery::mock(MetaOAuthService::class);
        $mockMetaService->shouldReceive('exchangeCodeForToken')
            ->once()
            ->with('valid_code', Mockery::any())
            ->andReturn(['access_token' => 'mock_token']);

        $mockMetaService->shouldReceive('getUserProfile')
            ->once()
            ->with('mock_token')
            ->andReturn([
                'id' => 'fb_123456789',
                'name' => 'Ahmed Facebook',
                'email' => 'ahmed.fb@example.com',
                'picture' => ['data' => ['url' => 'https://graph.facebook.com/pic.jpg']],
            ]);

        $this->app->instance(MetaOAuthService::class, $mockMetaService);

        session(['fb_auth_state' => 'secure_state_val']);

        $response = $this->get(route('auth.facebook.callback', [
            'code' => 'valid_code',
            'state' => 'secure_state_val',
        ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated('web');

        $user = User::where('email', 'ahmed.fb@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('fb_123456789', $user->facebook_id);
        $this->assertCount(1, $user->businesses);
        $this->assertNotNull($user->activeSubscription);
    }
}
