<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TiktokAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TikTokIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected string $clientKey = 'test_tiktok_client_key';

    protected string $clientSecret = 'test_tiktok_client_secret_xyz123';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.tiktok.client_key', $this->clientKey);
        Config::set('services.tiktok.client_secret', $this->clientSecret);
        Config::set('services.tiktok.redirect_uri', 'https://vertex.test/channels/tiktok/callback');

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'name' => 'TikTok Test Boutique',
        ]);
    }

    public function test_tiktok_oauth_redirect_generates_valid_authorization_url(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->get(route('tiktok.redirect', $this->business->id));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');

        $this->assertStringContainsString('https://www.tiktok.com/v2/auth/authorize/', $targetUrl);
        $this->assertStringContainsString('client_key='.$this->clientKey, $targetUrl);
        $this->assertStringContainsString('response_type=code', $targetUrl);
        $this->assertStringContainsString('user.info.basic', $targetUrl);
    }

    public function test_tiktok_oauth_callback_exchanges_code_and_creates_account(): void
    {
        Http::fake([
            'https://open.tiktokapis.com/v2/oauth/token/' => Http::response([
                'data' => [
                    'access_token' => 'act_tiktok_sample_access_token_123',
                    'refresh_token' => 'rft_tiktok_sample_refresh_token_456',
                    'open_id' => 'tiktok_user_open_id_999',
                    'expires_in' => 86400,
                    'refresh_expires_in' => 31536000,
                    'scope' => 'user.info.basic,im',
                ],
                'error' => ['code' => 'ok', 'message' => ''],
            ], 200),
            'https://open.tiktokapis.com/v2/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id' => 'tiktok_user_open_id_999',
                        'union_id' => 'union_999',
                        'avatar_url' => 'https://p16.tiktokcdn.com/avatar.jpg',
                        'display_name' => 'Boutique Official',
                        'username' => 'boutique_tiktok',
                    ],
                ],
                'error' => ['code' => 'ok', 'message' => ''],
            ], 200),
        ]);

        $state = 'test_state_123';

        $response = $this->actingAs($this->user)
            ->withSession([
                'current_business_id' => $this->business->id,
                'tiktok_oauth_state' => $state,
                'tiktok_oauth_business_id' => $this->business->id,
            ])
            ->get(route('tiktok.callback')."?code=valid_auth_code&state={$state}");

        $response->assertRedirect(route('channels.index', $this->business->id));

        $this->assertDatabaseHas('tiktok_accounts', [
            'business_id' => $this->business->id,
            'open_id' => 'tiktok_user_open_id_999',
            'username' => 'boutique_tiktok',
            'display_name' => 'Boutique Official',
            'status' => 'connected',
        ]);

        $account = TiktokAccount::where('business_id', $this->business->id)->first();
        $this->assertEquals('act_tiktok_sample_access_token_123', $account->access_token);
        $this->assertEquals('rft_tiktok_sample_refresh_token_456', $account->refresh_token);
    }

    public function test_tiktok_disconnect_removes_account(): void
    {
        TiktokAccount::create([
            'business_id' => $this->business->id,
            'open_id' => 'open_123',
            'username' => 'to_disconnect',
            'access_token' => 'token_xyz',
            'status' => 'connected',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->delete(route('tiktok.disconnect', $this->business->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('tiktok_accounts', [
            'business_id' => $this->business->id,
        ]);
    }

    public function test_tiktok_webhook_verifies_valid_signature_and_rejects_invalid(): void
    {
        $payload = [
            'event' => 'ping',
            'timestamp' => time(),
        ];
        $rawBody = json_encode($payload);
        $timestamp = (string) time();

        // 1. Invalid signature
        $invalidSigHeader = "t={$timestamp},s=invalid_fake_signature";
        $invalidResponse = $this->call(
            'POST',
            route('webhooks.tiktok'),
            [],
            [],
            [],
            [
                'HTTP_TikTok-Signature' => $invalidSigHeader,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $invalidResponse->assertStatus(401);

        // 2. Valid signature
        $messageToSign = $timestamp.'.'.$rawBody;
        $validHash = hash_hmac('sha256', $messageToSign, $this->clientSecret);
        $validSigHeader = "t={$timestamp},s={$validHash}";

        $validResponse = $this->call(
            'POST',
            route('webhooks.tiktok'),
            [],
            [],
            [],
            [
                'HTTP_TikTok-Signature' => $validSigHeader,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $validResponse->assertOk();
    }

    public function test_tiktok_webhook_processes_inbound_direct_message(): void
    {
        $account = TiktokAccount::create([
            'business_id' => $this->business->id,
            'open_id' => 'tiktok_bot_account_open_id',
            'username' => 'official_store',
            'access_token' => 'token_valid',
            'status' => 'connected',
        ]);

        $payload = [
            'event' => 'im.direct_message',
            'user_open_id' => 'tiktok_bot_account_open_id',
            'data' => [
                'from_user_id' => 'customer_tiktok_user_555',
                'to_user_id' => 'tiktok_bot_account_open_id',
                'conversation_id' => 'conv_tiktok_999',
                'message_id' => 'msg_tiktok_111',
                'type' => 'text',
                'content' => json_encode(['text' => 'مرحباً، ما هي أوقات العمل لديكم؟']),
                'create_time' => time(),
            ],
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) time();
        $messageToSign = $timestamp.'.'.$rawBody;
        $validHash = hash_hmac('sha256', $messageToSign, $this->clientSecret);

        $response = $this->call(
            'POST',
            route('webhooks.tiktok'),
            [],
            [],
            [],
            [
                'HTTP_TikTok-Signature' => "t={$timestamp},s={$validHash}",
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $response->assertOk();

        // Assert contact created
        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'platform' => 'tiktok',
            'platform_sender_id' => 'customer_tiktok_user_555',
        ]);

        // Assert conversation created
        $this->assertDatabaseHas('conversations', [
            'business_id' => $this->business->id,
            'platform' => 'tiktok',
        ]);

        // Assert message persisted
        $this->assertDatabaseHas('messages', [
            'sender_type' => 'contact',
            'body' => 'مرحباً، ما هي أوقات العمل لديكم؟',
            'platform_message_id' => 'msg_tiktok_111',
        ]);
    }
}
