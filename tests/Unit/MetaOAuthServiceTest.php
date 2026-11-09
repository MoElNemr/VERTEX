<?php

namespace Tests\Unit;

use App\Services\MetaOAuthService;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaOAuthServiceTest extends TestCase
{
    protected MetaOAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetaOAuthService;

        Config::set('services.meta.client_id', 'test_app_id_123');
        Config::set('services.meta.client_secret', 'test_app_secret_xyz');
        Config::set('services.meta.redirect_uri', 'https://vertex.test/channels/meta/callback');
        Config::set('services.meta.scopes', null);
    }

    public function test_get_client_id_and_secret(): void
    {
        $this->assertEquals('test_app_id_123', $this->service->getClientId());
        $this->assertEquals('test_app_secret_xyz', $this->service->getClientSecret());
    }

    public function test_get_authorization_url_contains_all_required_scopes_and_parameters(): void
    {
        $state = 'test_random_state_string_40_chars_long';
        $url = $this->service->getAuthorizationUrl($state);

        $this->assertStringStartsWith('https://www.facebook.com/v21.0/dialog/oauth?', $url);
        $this->assertStringContainsString('client_id=test_app_id_123', $url);
        $this->assertStringContainsString('state='.$state, $url);
        $this->assertStringContainsString('response_type=code', $url);

        // Verify all 8 permission scopes contract
        $requiredScopes = [
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_metadata',
            'pages_messaging',
            'instagram_basic',
            'instagram_manage_messages',
            'instagram_manage_comments',
            'public_profile',
        ];

        foreach ($requiredScopes as $scope) {
            $this->assertStringContainsString($scope, $url);
        }
    }

    public function test_get_scopes_returns_custom_scopes_when_configured(): void
    {
        Config::set('services.meta.scopes', 'pages_show_list,pages_messaging');
        $this->assertEquals(['pages_show_list', 'pages_messaging'], $this->service->getScopes());

        $url = $this->service->getAuthorizationUrl('st');
        $this->assertStringContainsString('scope=pages_show_list%2Cpages_messaging', $url);
    }

    public function test_exchange_code_for_token_success(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/oauth/access_token*' => Http::response([
                'access_token' => 'short_lived_token_abc',
                'token_type' => 'bearer',
                'expires_in' => 5184,
            ], 200),
        ]);

        $result = $this->service->exchangeCodeForToken('auth_code_123');

        $this->assertEquals('short_lived_token_abc', $result['access_token']);
        $this->assertEquals(5184, $result['expires_in']);
    }

    public function test_exchange_code_for_token_failure_throws_exception(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/oauth/access_token*' => Http::response([
                'error' => [
                    'message' => 'Invalid verification code format.',
                ],
            ], 400),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid verification code format.');

        $this->service->exchangeCodeForToken('invalid_code');
    }

    public function test_exchange_for_long_lived_token_success(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/oauth/access_token*' => Http::response([
                'access_token' => 'long_lived_token_60days',
                'token_type' => 'bearer',
                'expires_in' => 5184000,
            ], 200),
        ]);

        $result = $this->service->exchangeForLongLivedToken('short_lived_token_abc');

        $this->assertEquals('long_lived_token_60days', $result['access_token']);
        $this->assertEquals(5184000, $result['expires_in']);
    }

    public function test_get_user_profile_success(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/me*' => Http::response([
                'id' => 'meta_user_99999',
                'name' => 'John Admin',
            ], 200),
        ]);

        $profile = $this->service->getUserProfile('user_token_123');

        $this->assertEquals('meta_user_99999', $profile['id']);
        $this->assertEquals('John Admin', $profile['name']);
    }

    public function test_get_manageable_accounts_success(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => 'page_111',
                        'name' => 'Fashion Boutique',
                        'category' => 'Retail',
                        'access_token' => 'permanent_page_token_111',
                        'picture' => [
                            'data' => [
                                'url' => 'https://facebook.com/avatar111.jpg',
                            ],
                        ],
                        'instagram_business_account' => [
                            'id' => 'ig_biz_222',
                            'username' => 'fashion_boutique_ig',
                            'profile_picture_url' => 'https://instagram.com/avatar222.jpg',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $accounts = $this->service->getManageableAccounts('long_lived_token_xyz');

        $this->assertCount(1, $accounts);
        $this->assertEquals('page_111', $accounts[0]['id']);
        $this->assertEquals('permanent_page_token_111', $accounts[0]['access_token']);
        $this->assertEquals('ig_biz_222', $accounts[0]['instagram_business_account']['id']);
    }

    public function test_subscribe_page_webhook_success(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/page_111/subscribed_apps' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $result = $this->service->subscribePageWebhook('page_111', 'page_token_111');

        $this->assertTrue($result);
    }

    public function test_parse_signed_request_valid(): void
    {
        $secret = 'test_app_secret_xyz';
        $payload = [
            'user_id' => 'meta_user_12345',
            'algorithm' => 'HMAC-SHA256',
            'issued_at' => time(),
        ];

        $encodedPayload = strtr(rtrim(base64_encode(json_encode($payload)), '='), '+/', '-_');
        $rawSig = hash_hmac('sha256', $encodedPayload, $secret, true);
        $encodedSig = strtr(rtrim(base64_encode($rawSig), '='), '+/', '-_');

        $signedRequest = $encodedSig.'.'.$encodedPayload;

        $parsed = $this->service->parseSignedRequest($signedRequest);

        $this->assertNotNull($parsed);
        $this->assertEquals('meta_user_12345', $parsed['user_id']);
    }

    public function test_parse_signed_request_invalid_signature_returns_null(): void
    {
        $payload = ['user_id' => 'meta_user_fake'];
        $encodedPayload = strtr(rtrim(base64_encode(json_encode($payload)), '='), '+/', '-_');
        $invalidSig = strtr(rtrim(base64_encode('tampered_signature_bytes'), '='), '+/', '-_');

        $signedRequest = $invalidSig.'.'.$encodedPayload;

        $parsed = $this->service->parseSignedRequest($signedRequest);

        $this->assertNull($parsed);
    }
}
