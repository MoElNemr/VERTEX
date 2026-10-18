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
        // 1. Connection update
        $connPayload = [
            'event' => 'connection.update',
            'data' => [
                'session_id' => 'session_' . $this->business->id,
                'status' => 'connecting',
                'phone_number' => '201099887766',
            ],
        ];

        $connResponse = $this->postJson(route('webhooks.whatsapp', $this->business->id), $connPayload);
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

        $msgResponse = $this->postJson(route('webhooks.whatsapp', $this->business->id), $msgPayload);
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
}
