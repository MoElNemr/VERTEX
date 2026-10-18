<?php

namespace Tests\Feature\Webhooks;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\FacebookAccount;
use App\Models\InstagramAccount;
use App\Models\Message;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\Channels\WhatsAppTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelWebhooksTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $user->id,
            'name' => 'Test Business Hub',
        ]);
    }

    public function test_telegram_webhook_creates_contact_conversation_and_message(): void
    {
        TelegramAccount::create([
            'business_id' => $this->business->id,
            'bot_username' => 'TestBot',
            'bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'status' => 'connected',
        ]);

        $payload = [
            'update_id' => 987654,
            'message' => [
                'message_id' => 456,
                'from' => [
                    'id' => 99887766,
                    'first_name' => 'Omar',
                    'last_name' => 'El-Alfy',
                    'username' => 'omarelalfy',
                ],
                'chat' => [
                    'id' => 99887766,
                    'type' => 'private',
                ],
                'text' => 'مرحباً، هل يتوفر شحن للإسكندرية؟',
                'date' => time(),
            ],
        ];

        $response = $this->postJson(route('webhooks.telegram', $this->business->id), $payload);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'platform' => 'telegram',
            'platform_sender_id' => '99887766',
            'name' => 'Omar El-Alfy',
        ]);

        $this->assertDatabaseHas('conversations', [
            'business_id' => $this->business->id,
            'platform' => 'telegram',
            'unread_count' => 1,
        ]);

        $this->assertDatabaseHas('messages', [
            'sender_type' => 'contact',
            'body' => 'مرحباً، هل يتوفر شحن للإسكندرية؟',
            'platform_message_id' => '456',
        ]);
    }

    public function test_meta_webhook_verification_returns_challenge(): void
    {
        $challenge = 'random_challenge_code_123456';

        $response = $this->get(route('webhooks.meta.verify', [
            'business' => $this->business->id,
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'vertex_meta_token_' . $this->business->id,
            'hub_challenge' => $challenge,
        ]));

        $response->assertOk();
        $this->assertEquals($challenge, $response->getContent());
    }

    public function test_meta_webhook_verification_fails_closed_in_production_if_verify_token_missing(): void
    {
        $this->app['env'] = 'production';
        config(['services.meta.verify_token' => null]);

        $response = $this->get(route('webhooks.meta.verify', [
            'business' => $this->business->id,
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'some_token',
            'hub_challenge' => 'challenge_code',
        ]));

        $response->assertStatus(403);
    }

    public function test_meta_webhook_verification_accepts_configured_token_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['services.meta.verify_token' => 'configured_prod_verify_secret']);

        $response = $this->get(route('webhooks.meta.verify', [
            'business' => $this->business->id,
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'configured_prod_verify_secret',
            'hub_challenge' => 'prod_challenge_code',
        ]));

        $response->assertOk();
        $this->assertEquals('prod_challenge_code', $response->getContent());
    }

    public function test_meta_facebook_webhook_creates_message(): void
    {
        FacebookAccount::create([
            'business_id' => $this->business->id,
            'page_id' => '100200300',
            'page_name' => 'Test Page',
            'page_access_token' => 'EAATestToken',
            'status' => 'connected',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100200300',
                    'time' => 1728000000000,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'psid_user_777'],
                            'recipient' => ['id' => '100200300'],
                            'message' => [
                                'mid' => 'mid.fb_message_777',
                                'text' => 'تفاصيل العرض لو سمحت',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(route('webhooks.meta', $this->business->id), $payload);

        $response->assertOk();

        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'platform' => 'facebook',
            'platform_sender_id' => 'psid_user_777',
        ]);

        $this->assertDatabaseHas('messages', [
            'sender_type' => 'contact',
            'body' => 'تفاصيل العرض لو سمحت',
            'platform_message_id' => 'mid.fb_message_777',
        ]);
    }

    public function test_meta_instagram_webhook_creates_message(): void
    {
        InstagramAccount::create([
            'business_id' => $this->business->id,
            'instagram_business_id' => 'ig_biz_400',
            'username' => 'teststore.ig',
            'access_token' => 'EAATestTokenIG',
            'status' => 'connected',
        ]);

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_biz_400',
                    'time' => 1728000000000,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'igsid_user_999'],
                            'recipient' => ['id' => 'ig_biz_400'],
                            'message' => [
                                'mid' => 'mid.ig_message_999',
                                'text' => 'المنتج متوفر بالمقاس ده؟',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(route('webhooks.meta', $this->business->id), $payload);

        $response->assertOk();

        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'platform' => 'instagram',
            'platform_sender_id' => 'igsid_user_999',
        ]);

        $this->assertDatabaseHas('messages', [
            'sender_type' => 'contact',
            'body' => 'المنتج متوفر بالمقاس ده؟',
            'platform_message_id' => 'mid.ig_message_999',
        ]);
    }

    public function test_whatsapp_webhook_handles_connection_and_incoming_message(): void
    {
        $headers = ['X-Server-Token' => WhatsAppTokenService::getToken()];

        // 1. Connection update
        $connPayload = [
            'event' => 'connection.update',
            'data' => [
                'session_id' => 'session_' . $this->business->id,
                'status' => 'connecting',
                'phone_number' => '201099887766',
            ],
        ];

        $connResponse = $this->postJson(route('webhooks.whatsapp', $this->business->id), $connPayload, $headers);
        $connResponse->assertOk();

        $this->assertDatabaseHas('whatsapp_accounts', [
            'business_id' => $this->business->id,
            'status' => 'connecting',
            'phone_number' => '201099887766',
        ]);

        // 2. Incoming message update
        $msgPayload = [
            'event' => 'messages.upsert',
            'data' => [
                'from' => '201099887766@s.whatsapp.net',
                'push_name' => 'Mostafa Mahmoud',
                'message_id' => 'BAE5_MSG_12345',
                'text' => 'عاوز أطلب أوردر جديد',
            ],
        ];

        $msgResponse = $this->postJson(route('webhooks.whatsapp', $this->business->id), $msgPayload, $headers);
        $msgResponse->assertOk();

        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'platform' => 'whatsapp',
            'phone' => '201099887766',
            'name' => 'Mostafa Mahmoud',
        ]);

        $this->assertDatabaseHas('messages', [
            'sender_type' => 'contact',
            'body' => 'عاوز أطلب أوردر جديد',
            'platform_message_id' => 'BAE5_MSG_12345',
        ]);
    }

    public function test_whatsapp_webhook_rejects_unauthenticated_requests(): void
    {
        $payload = [
            'event' => 'connection.update',
            'data' => [
                'session_id' => 'session_' . $this->business->id,
                'status' => 'connecting',
            ],
        ];

        // Without X-Server-Token or X-Shared-Secret
        $response = $this->postJson(route('webhooks.whatsapp', $this->business->id), $payload);
        $response->assertStatus(401);
        $response->assertJson(['status' => 'unauthorized']);
    }

    public function test_whatsapp_webhook_accepts_valid_shared_secret(): void
    {
        $payload = [
            'event' => 'connection.update',
            'data' => [
                'session_id' => 'session_' . $this->business->id,
                'status' => 'connected',
                'phone_number' => '201011223344',
            ],
        ];

        $secret = WhatsAppTokenService::getSecret();
        $response = $this->postJson(route('webhooks.whatsapp', $this->business->id), $payload, [
            'X-Shared-Secret' => $secret,
        ]);

        $response->assertOk();
    }

    public function test_whatsapp_webhook_fails_closed_in_production_if_secret_missing(): void
    {
        $this->app['env'] = 'production';
        config(['services.whatsapp.secret' => null]);

        $payload = ['event' => 'connection.update', 'data' => []];
        $response = $this->postJson(route('webhooks.whatsapp', $this->business->id), $payload);

        $response->assertStatus(401);
    }

    public function test_meta_webhook_rejects_missing_signature_when_app_secret_is_configured(): void
    {
        config(['services.meta.app_secret' => 'meta_test_secret_key_123']);

        FacebookAccount::create([
            'business_id' => $this->business->id,
            'page_id' => '100200301',
            'page_name' => 'Test Page',
            'page_access_token' => 'EAATestToken',
            'status' => 'connected',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [],
        ];

        // Post without X-Hub-Signature-256 header
        $response = $this->postJson(route('webhooks.meta', $this->business->id), $payload);
        $response->assertStatus(401);
    }

    public function test_meta_webhook_rejects_invalid_signature_when_app_secret_is_configured(): void
    {
        config(['services.meta.app_secret' => 'meta_test_secret_key_123']);

        $payload = ['object' => 'page', 'entry' => []];

        $response = $this->postJson(route('webhooks.meta', $this->business->id), $payload, [
            'X-Hub-Signature-256' => 'sha256=invalid_hash_value',
        ]);
        $response->assertStatus(401);
    }

    public function test_meta_webhook_accepts_valid_signature_when_app_secret_is_configured(): void
    {
        $secret = 'meta_test_secret_key_123';
        config(['services.meta.app_secret' => $secret]);

        FacebookAccount::create([
            'business_id' => $this->business->id,
            'page_id' => '100200302',
            'page_name' => 'Signed Page',
            'page_access_token' => 'EAATestToken',
            'status' => 'connected',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100200302',
                    'time' => 1728000000000,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'psid_user_sig'],
                            'recipient' => ['id' => '100200302'],
                            'message' => [
                                'mid' => 'mid.fb_signed_message',
                                'text' => 'رسالة مؤكدة بالتوقيع الرقمي',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);

        $response = $this->call(
            'POST',
            route('webhooks.meta', $this->business->id),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $jsonPayload
        );

        $response->assertOk();
        $this->assertDatabaseHas('messages', [
            'body' => 'رسالة مؤكدة بالتوقيع الرقمي',
            'platform_message_id' => 'mid.fb_signed_message',
        ]);
    }

    public function test_meta_webhook_fails_closed_in_production_if_app_secret_missing(): void
    {
        $this->app['env'] = 'production';
        config(['services.meta.app_secret' => null]);

        $payload = ['object' => 'page', 'entry' => []];
        $response = $this->postJson(route('webhooks.meta', $this->business->id), $payload);

        $response->assertStatus(401);
    }

    public function test_inbox_send_message_dispatches_outbound(): void
    {
        $contact = Contact::create([
            'business_id' => $this->business->id,
            'platform' => 'whatsapp',
            'platform_sender_id' => '201012345678',
            'name' => 'Ahmed Wa Contact',
            'phone' => '201012345678',
        ]);

        $conversation = Conversation::create([
            'business_id' => $this->business->id,
            'contact_id' => $contact->id,
            'platform' => 'whatsapp',
            'status' => 'open',
        ]);

        WhatsappAccount::create([
            'business_id' => $this->business->id,
            'session_id' => 'session_' . $this->business->id,
            'phone_number' => '+201012345678',
            'status' => 'connected',
        ]);

        $user = $this->business->user;

        $response = $this->actingAs($user)
            ->withSession(['current_business_id' => $this->business->id])
            ->postJson(route('inbox.send', $conversation->id), [
                'body' => 'أهلاً بك يا أحمد، تم استلام طلبك بنجاح!',
            ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'body' => 'أهلاً بك يا أحمد، تم استلام طلبك بنجاح!',
            'status' => 'delivered',
        ]);
    }

    public function test_webhook_simulator_creates_telegram_conversation(): void
    {
        $response = $this->postJson(route('webhooks.simulate.telegram', $this->business->id), [
            'sender_id' => 'sim_12345',
            'sender_name' => 'Simulated User',
            'text' => 'رسالة محاكاة لاختبار تيليجرام',
        ]);

        $response->assertOk();
        $response->assertJson([
            'simulated_platform' => 'telegram',
            'result' => ['status' => 'success'],
        ]);

        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'platform' => 'telegram',
            'platform_sender_id' => 'sim_12345',
        ]);
    }

    public function test_webhook_simulator_creates_facebook_message_and_bypasses_signature_in_testing(): void
    {
        config(['services.meta.app_secret' => 'meta_secret_active_123']);

        $response = $this->postJson(route('webhooks.simulate.facebook', $this->business->id), [
            'sender_id' => 'sim_fb_777',
            'sender_name' => 'Simulated FB User',
            'text' => 'رسالة محاكاة فيسبوك مع تفعيل السيكرت',
        ]);

        $response->assertOk();
        $response->assertJson([
            'simulated_platform' => 'facebook',
            'result' => ['status' => 'success'],
        ]);

        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'platform' => 'facebook',
            'platform_sender_id' => 'sim_fb_777',
        ]);
    }

    public function test_webhook_simulator_creates_whatsapp_message_and_bypasses_token_in_testing(): void
    {
        config(['services.whatsapp.secret' => 'wa_secret_active_456']);

        $response = $this->postJson(route('webhooks.simulate.whatsapp', $this->business->id), [
            'phone' => '201099881122',
            'sender_name' => 'Simulated WA User',
            'text' => 'رسالة محاكاة واتساب بدون توكن',
        ]);

        $response->assertOk();
        $response->assertJson([
            'simulated_platform' => 'whatsapp',
            'result' => ['status' => 'success'],
        ]);

        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'platform' => 'whatsapp',
            'phone' => '201099881122',
        ]);
    }

    public function test_simulator_routes_are_strictly_blocked_in_production_even_with_enable_simulator_flag(): void
    {
        $this->app['env'] = 'production';
        putenv('ENABLE_SIMULATOR=true');
        $_ENV['ENABLE_SIMULATOR'] = 'true';

        // 1. Verify route-level gate logic strictly evaluates to false in production
        $shouldRegister = !app()->isProduction() && (app()->environment(['local', 'testing']) || env('ENABLE_SIMULATOR', false));
        $this->assertFalse($shouldRegister);

        // 2. Verify controller-level defense aborts with 404 in production
        $response = $this->postJson(route('webhooks.simulate.facebook', $this->business->id), [
            'text' => 'محاولة محاكاة في بيئة الإنتاج',
        ]);

        $response->assertStatus(404);

        // Restore environment
        $this->app['env'] = 'testing';
    }
}
