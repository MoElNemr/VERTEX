<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\FacebookAccount;
use App\Models\InstagramAccount;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MetaOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaOAuthSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $otherUser;

    protected Business $business;

    protected Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.meta.app_id', 'test_app_id_999');
        Config::set('services.meta.client_id', 'test_app_id_999');
        Config::set('services.meta.app_secret', 'test_app_secret_888');
        Config::set('services.meta.client_secret', 'test_app_secret_888');
        Config::set('services.meta.redirect_uri', 'https://vertex.test/channels/meta/callback');
        Config::set('services.meta.scopes', null);

        $plan = Plan::create([
            'name' => 'Pro Business Plan',
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
            'name' => 'Owner Store',
        ]);

        $this->otherUser = User::factory()->create();
        Subscription::create([
            'user_id' => $this->otherUser->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $this->otherBusiness = Business::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other Store',
        ]);
    }

    public function test_redirect_to_meta_generates_state_and_redirects(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('meta.redirect', $this->business->id));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');

        $this->assertStringContainsString('https://www.facebook.com/v21.0/dialog/oauth', $targetUrl);
        $this->assertStringContainsString('client_id=test_app_id_999', $targetUrl);
        $this->assertStringContainsString('pages_messaging', $targetUrl);
        $this->assertStringContainsString('instagram_manage_messages', $targetUrl);

        $this->assertNotEmpty(session('meta_oauth_state'));
        $this->assertEquals($this->business->id, session('meta_oauth_business_id'));
    }

    public function test_redirect_to_meta_unauthorized_user_forbidden(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->get(route('meta.redirect', $this->business->id));

        $response->assertStatus(403);
    }

    public function test_callback_fails_when_state_mismatch(): void
    {
        session([
            'meta_oauth_state' => 'original_valid_state_123',
            'meta_oauth_business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('meta.callback', [
                'state' => 'tampered_state_999',
                'code' => 'meta_code_abc',
            ]));

        $response->assertStatus(403);
    }

    public function test_callback_handles_user_cancellation_or_meta_error(): void
    {
        session([
            'meta_oauth_state' => 'valid_state_123',
            'meta_oauth_business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('meta.callback', [
                'error' => 'access_denied',
                'error_description' => 'Permissions error occurred.',
            ]));

        $response->assertRedirect(route('channels.index', ['business' => $this->business->id]));
        $response->assertSessionHas('error');
    }

    public function test_callback_missing_code_redirects_with_error(): void
    {
        session([
            'meta_oauth_state' => 'valid_state_123',
            'meta_oauth_business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('meta.callback', [
                'state' => 'valid_state_123',
            ]));

        $response->assertRedirect(route('channels.index', ['business' => $this->business->id]));
        $response->assertSessionHas('error');
    }

    public function test_callback_executes_three_tier_token_exchange_and_stores_session(): void
    {
        session([
            'meta_oauth_state' => 'valid_state_123',
            'meta_oauth_business_id' => $this->business->id,
        ]);

        Http::fake([
            // 1. Code -> Short-lived token
            'https://graph.facebook.com/v21.0/oauth/access_token*code=auth_code_xyz*' => Http::response([
                'access_token' => 'short_token_111',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),

            // 2. Short-lived -> Long-lived token
            'https://graph.facebook.com/v21.0/oauth/access_token*grant_type=fb_exchange_token*' => Http::response([
                'access_token' => 'long_token_222',
                'token_type' => 'bearer',
                'expires_in' => 5184000,
            ], 200),

            // Profile info
            'https://graph.facebook.com/v21.0/me?*' => Http::response([
                'id' => 'admin_user_444',
                'name' => 'Owner Admin',
            ], 200),

            // 3. Long-lived token -> Accounts with permanent page token
            'https://graph.facebook.com/v21.0/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => 'page_777',
                        'name' => 'Prime Electronics Page',
                        'category' => 'Electronics Store',
                        'access_token' => 'permanent_page_token_777',
                        'picture' => [
                            'data' => [
                                'url' => 'https://facebook.com/page777.jpg',
                            ],
                        ],
                        'instagram_business_account' => [
                            'id' => 'ig_biz_888',
                            'username' => 'prime_electronics_ig',
                            'profile_picture_url' => 'https://instagram.com/ig888.jpg',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('meta.callback', [
                'state' => 'valid_state_123',
                'code' => 'auth_code_xyz',
            ]));

        $response->assertRedirect(route('channels.index', [
            'business' => $this->business->id,
            'select_meta_asset' => 1,
        ]));

        $this->assertEquals('admin_user_444', session('meta_user_id'));
        $this->assertNotEmpty(session('meta_oauth_accounts'));
        $this->assertEquals('page_777', session('meta_oauth_accounts')[0]['id']);
        $this->assertEquals('permanent_page_token_777', session('meta_oauth_accounts')[0]['access_token']);
    }

    public function test_callback_when_no_accounts_found_redirects_with_warning(): void
    {
        session([
            'meta_oauth_state' => 'valid_state_123',
            'meta_oauth_business_id' => $this->business->id,
        ]);

        Http::fake([
            'https://graph.facebook.com/v21.0/oauth/access_token*code=auth_code_xyz*' => Http::response([
                'access_token' => 'short_token_111',
            ], 200),
            'https://graph.facebook.com/v21.0/oauth/access_token*grant_type=fb_exchange_token*' => Http::response([
                'access_token' => 'long_token_222',
            ], 200),
            'https://graph.facebook.com/v21.0/me?*' => Http::response([
                'id' => 'admin_user_444',
            ], 200),
            'https://graph.facebook.com/v21.0/me/accounts*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('meta.callback', [
                'state' => 'valid_state_123',
                'code' => 'auth_code_xyz',
            ]));

        $response->assertRedirect(route('channels.index', ['business' => $this->business->id]));
        $response->assertSessionHas('error');
    }

    public function test_persist_asset_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('meta.persist-asset', $this->business->id), []);

        $response->assertSessionHasErrors(['page_id']);
    }

    public function test_persist_asset_unauthorized_user_forbidden(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->post(route('meta.persist-asset', $this->business->id), [
                'page_id' => 'page_777',
            ]);

        $response->assertStatus(403);
    }

    public function test_persist_asset_session_expired_redirects_with_error(): void
    {
        session()->forget('meta_oauth_accounts');

        $response = $this->actingAs($this->owner)
            ->post(route('meta.persist-asset', $this->business->id), [
                'page_id' => 'page_777',
            ]);

        $response->assertRedirect(route('channels.index', ['business' => $this->business->id]));
        $response->assertSessionHas('error');
    }

    public function test_persist_asset_selected_page_not_in_session_returns_error(): void
    {
        session([
            'meta_oauth_accounts' => [
                ['id' => 'other_page_555', 'name' => 'Other', 'access_token' => 'tok'],
            ],
            'meta_user_id' => 'admin_123',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('meta.persist-asset', $this->business->id), [
                'page_id' => 'unauthorized_page_999',
            ]);

        $response->assertRedirect(route('channels.index', ['business' => $this->business->id]));
        $response->assertSessionHas('error');
    }

    public function test_persist_asset_facebook_only(): void
    {
        session([
            'meta_oauth_accounts' => [
                [
                    'id' => 'page_100',
                    'name' => 'Alpha Motors',
                    'access_token' => 'permanent_page_token_alpha',
                    'picture' => [
                        'data' => [
                            'url' => 'https://facebook.com/avatar_alpha.jpg',
                        ],
                    ],
                    'instagram_business_account' => [
                        'id' => 'ig_alpha',
                        'username' => 'alpha_motors_ig',
                    ],
                ],
            ],
            'meta_user_id' => 'meta_admin_alpha',
            'meta_token_expires_at' => now()->addDays(60)->toDateTimeString(),
            'meta_granted_scopes' => MetaOAuthService::SCOPES,
        ]);

        Http::fake([
            'https://graph.facebook.com/v21.0/page_100/subscribed_apps' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('meta.persist-asset', $this->business->id), [
                'page_id' => 'page_100',
                'connect_instagram' => false,
            ]);

        $response->assertRedirect(route('channels.index', ['business' => $this->business->id]));
        $response->assertSessionHas('success');

        // Check FacebookAccount persisted
        $this->assertDatabaseHas('facebook_accounts', [
            'business_id' => $this->business->id,
            'page_id' => 'page_100',
            'page_name' => 'Alpha Motors',
            'meta_user_id' => 'meta_admin_alpha',
            'token_type' => 'oauth',
            'avatar_url' => 'https://facebook.com/avatar_alpha.jpg',
            'status' => 'connected',
        ]);

        // Instagram should not be connected
        $this->assertDatabaseMissing('instagram_accounts', [
            'business_id' => $this->business->id,
        ]);

        // Temporary session data cleared
        $this->assertNull(session('meta_oauth_accounts'));
        $this->assertNull(session('meta_user_id'));
    }

    public function test_persist_asset_facebook_and_instagram_connected(): void
    {
        session([
            'meta_oauth_accounts' => [
                [
                    'id' => 'page_200',
                    'name' => 'Omega Watches',
                    'access_token' => 'permanent_token_omega',
                    'picture' => [
                        'data' => [
                            'url' => 'https://facebook.com/omega_page.jpg',
                        ],
                    ],
                    'instagram_business_account' => [
                        'id' => 'ig_omega_watch',
                        'username' => 'omega_official',
                        'profile_picture_url' => 'https://instagram.com/omega_ig.jpg',
                    ],
                ],
            ],
            'meta_user_id' => 'meta_admin_omega',
            'meta_token_expires_at' => now()->addDays(60)->toDateTimeString(),
            'meta_granted_scopes' => MetaOAuthService::SCOPES,
        ]);

        Http::fake([
            'https://graph.facebook.com/v21.0/page_200/subscribed_apps' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('meta.persist-asset', $this->business->id), [
                'page_id' => 'page_200',
                'connect_instagram' => true,
            ]);

        $response->assertRedirect(route('channels.index', ['business' => $this->business->id]));
        $response->assertSessionHas('success');

        // Facebook page connected
        $this->assertDatabaseHas('facebook_accounts', [
            'business_id' => $this->business->id,
            'page_id' => 'page_200',
            'page_name' => 'Omega Watches',
            'meta_user_id' => 'meta_admin_omega',
            'token_type' => 'oauth',
            'avatar_url' => 'https://facebook.com/omega_page.jpg',
            'status' => 'connected',
        ]);

        // Instagram connected with permanent page token
        $this->assertDatabaseHas('instagram_accounts', [
            'business_id' => $this->business->id,
            'instagram_business_id' => 'ig_omega_watch',
            'username' => 'omega_official',
            'meta_user_id' => 'meta_admin_omega',
            'linked_facebook_page_id' => 'page_200',
            'token_type' => 'oauth',
            'avatar_url' => 'https://instagram.com/omega_ig.jpg',
            'status' => 'connected',
        ]);

        $fbAccount = FacebookAccount::withoutGlobalScopes()->where('business_id', $this->business->id)->first();
        $igAccount = InstagramAccount::withoutGlobalScopes()->where('business_id', $this->business->id)->first();

        // Must share the exact same permanent access token
        $this->assertEquals('permanent_token_omega', $fbAccount->page_access_token);
        $this->assertEquals('permanent_token_omega', $igAccount->access_token);
    }

    public function test_disconnect_channel_facebook_only(): void
    {
        FacebookAccount::create([
            'business_id' => $this->business->id,
            'page_id' => 'fb_123',
            'page_name' => 'Test FB',
            'page_access_token' => 'tok',
        ]);

        InstagramAccount::create([
            'business_id' => $this->business->id,
            'instagram_business_id' => 'ig_123',
            'username' => 'test_ig',
            'access_token' => 'tok',
        ]);

        $res = $this->actingAs($this->owner)
            ->delete(route('meta.disconnect', ['business' => $this->business->id, 'channel' => 'facebook']));

        $res->assertRedirect();
        $this->assertDatabaseMissing('facebook_accounts', ['business_id' => $this->business->id]);
        $this->assertDatabaseHas('instagram_accounts', ['business_id' => $this->business->id]);
    }

    public function test_disconnect_channel_both(): void
    {
        FacebookAccount::create([
            'business_id' => $this->business->id,
            'page_id' => 'fb_123',
            'page_name' => 'Test FB',
            'page_access_token' => 'tok',
        ]);

        InstagramAccount::create([
            'business_id' => $this->business->id,
            'instagram_business_id' => 'ig_123',
            'username' => 'test_ig',
            'access_token' => 'tok',
        ]);

        $res = $this->actingAs($this->owner)
            ->delete(route('meta.disconnect', ['business' => $this->business->id, 'channel' => 'both']));

        $res->assertRedirect();
        $this->assertDatabaseMissing('facebook_accounts', ['business_id' => $this->business->id]);
        $this->assertDatabaseMissing('instagram_accounts', ['business_id' => $this->business->id]);
    }

    public function test_compliance_data_deletion_endpoint_csrf_exempt_and_purges_tokens(): void
    {
        FacebookAccount::create([
            'business_id' => $this->business->id,
            'page_id' => 'fb_del_1',
            'page_name' => 'Del Page',
            'meta_user_id' => 'meta_target_user_777',
            'page_access_token' => 'secret_token_to_purge',
        ]);

        InstagramAccount::create([
            'business_id' => $this->business->id,
            'instagram_business_id' => 'ig_del_1',
            'meta_user_id' => 'meta_target_user_777',
            'access_token' => 'secret_token_to_purge',
        ]);

        $secret = 'test_app_secret_888';
        $payload = [
            'user_id' => 'meta_target_user_777',
            'algorithm' => 'HMAC-SHA256',
        ];

        $encodedPayload = strtr(rtrim(base64_encode(json_encode($payload)), '='), '+/', '-_');
        $rawSig = hash_hmac('sha256', $encodedPayload, $secret, true);
        $encodedSig = strtr(rtrim(base64_encode($rawSig), '='), '+/', '-_');
        $signedRequest = $encodedSig.'.'.$encodedPayload;

        $response = $this->postJson(route('meta.data-deletion'), [
            'signed_request' => $signedRequest,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['url', 'confirmation_code']);

        // Accounts must be purged
        $this->assertDatabaseMissing('facebook_accounts', ['meta_user_id' => 'meta_target_user_777']);
        $this->assertDatabaseMissing('instagram_accounts', ['meta_user_id' => 'meta_target_user_777']);

        // Test status confirmation endpoint
        $statusUrl = $response->json('url');
        $statusRes = $this->getJson($statusUrl);
        $statusRes->assertOk();
        $statusRes->assertJson(['status' => 'completed']);
    }

    public function test_compliance_data_deletion_invalid_signature_rejected(): void
    {
        $signedRequest = 'invalid_sig.invalid_payload';

        $response = $this->postJson(route('meta.data-deletion'), [
            'signed_request' => $signedRequest,
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_security_hardening_missing_signature_rejected(): void
    {
        $response = $this->post(route('webhooks.meta', $this->business->id), [
            'object' => 'page',
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_security_hardening_invalid_signature_rejected(): void
    {
        $response = $this->withHeaders([
            'X-Hub-Signature-256' => 'sha256=invalid_hash_value_123',
        ])->post(route('webhooks.meta', $this->business->id), [
            'object' => 'page',
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_security_hardening_valid_signature_accepted(): void
    {
        FacebookAccount::create([
            'business_id' => $this->business->id,
            'page_id' => '100200300',
            'page_name' => 'Security Test Page',
            'page_access_token' => 'EAATokenSec',
            'status' => 'connected',
        ]);

        $payload = json_encode([
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100200300',
                    'time' => 1728000000000,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'psid_user_security'],
                            'recipient' => ['id' => '100200300'],
                            'message' => [
                                'mid' => 'mid.sec_msg_1',
                                'text' => 'Hello security check',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $expectedSig = hash_hmac('sha256', $payload, 'test_app_secret_888');

        $response = $this->call(
            'POST',
            route('webhooks.meta', $this->business->id),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.$expectedSig,
            ],
            $payload
        );

        $response->assertOk();
    }

    public function test_tenant_scoping_and_isolation(): void
    {
        // Business 1 connects Facebook
        FacebookAccount::create([
            'business_id' => $this->business->id,
            'page_id' => 'page_tenant_1',
            'page_name' => 'Tenant 1 Page',
            'page_access_token' => 'tok_1',
        ]);

        // Business 2 connects Facebook
        FacebookAccount::create([
            'business_id' => $this->otherBusiness->id,
            'page_id' => 'page_tenant_2',
            'page_name' => 'Tenant 2 Page',
            'page_access_token' => 'tok_2',
        ]);

        // Tenant 1 acting as owner 1 tries to disconnect Tenant 2's channel
        $res = $this->actingAs($this->owner)
            ->delete(route('meta.disconnect', ['business' => $this->otherBusiness->id, 'channel' => 'facebook']));

        $res->assertStatus(403);

        // Tenant 2's Facebook account remains intact
        $this->assertDatabaseHas('facebook_accounts', [
            'business_id' => $this->otherBusiness->id,
            'page_id' => 'page_tenant_2',
        ]);

        // BelongsToBusiness global scope when session is active
        session(['current_business_id' => $this->business->id]);
        $scopedAccount = FacebookAccount::first();
        $this->assertEquals('page_tenant_1', $scopedAccount->page_id);

        session(['current_business_id' => $this->otherBusiness->id]);
        $scopedAccount2 = FacebookAccount::first();
        $this->assertEquals('page_tenant_2', $scopedAccount2->page_id);
    }
}
