<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppMultiStoreSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $businessA;
    protected Business $businessB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->businessA = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Store Alpha',
        ]);

        $this->businessB = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Store Beta',
        ]);
    }

    public function test_incoming_whatsapp_message_syncs_across_stores_sharing_same_number(): void
    {
        $sharedPhone = '201012345678';

        WhatsappAccount::create([
            'business_id' => $this->businessA->id,
            'session_id' => 'session_alpha',
            'phone_number' => $sharedPhone,
            'status' => 'connected',
        ]);

        WhatsappAccount::create([
            'business_id' => $this->businessB->id,
            'session_id' => 'session_beta',
            'phone_number' => $sharedPhone,
            'status' => 'connected',
        ]);

        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'session_id' => 'session_alpha',
                'business_id' => $this->businessA->id,
                'account_phone_number' => $sharedPhone,
                'from' => '201099887766@s.whatsapp.net',
                'is_from_me' => false,
                'push_name' => 'Mohamed Customer',
                'message_id' => 'WA_TEST_MSG_001',
                'text' => 'أريد الاستفسار عن المنتجات',
            ],
        ];

        $response = $this->postJson(route('webhooks.whatsapp', $this->businessA->id), $payload);
        $response->assertStatus(200);

        // Assert message recorded in Store Alpha
        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->businessA->id,
            'phone' => '201099887766',
            'platform' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('conversations', [
            'business_id' => $this->businessA->id,
            'platform' => 'whatsapp',
            'unread_count' => 1,
        ]);
        $this->assertDatabaseHas('messages', [
            'platform_message_id' => 'WA_TEST_MSG_001',
            'body' => 'أريد الاستفسار عن المنتجات',
            'sender_type' => 'contact',
        ]);

        // Assert message also synced to Store Beta
        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->businessB->id,
            'phone' => '201099887766',
            'platform' => 'whatsapp',
        ]);
        $this->assertDatabaseHas('conversations', [
            'business_id' => $this->businessB->id,
            'platform' => 'whatsapp',
            'unread_count' => 1,
        ]);
    }

    public function test_outbound_message_from_phone_syncs_as_user_reply_without_increasing_unread_count(): void
    {
        $sharedPhone = '201012345678';

        WhatsappAccount::create([
            'business_id' => $this->businessA->id,
            'session_id' => 'session_alpha',
            'phone_number' => $sharedPhone,
            'status' => 'connected',
        ]);

        WhatsappAccount::create([
            'business_id' => $this->businessB->id,
            'session_id' => 'session_beta',
            'phone_number' => $sharedPhone,
            'status' => 'connected',
        ]);

        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'session_id' => 'session_alpha',
                'business_id' => $this->businessA->id,
                'account_phone_number' => $sharedPhone,
                'from' => '201099887766@s.whatsapp.net',
                'is_from_me' => true,
                'message_id' => 'WA_OUTBOUND_001',
                'text' => 'أهلاً بك، تم شحن طلبك!',
            ],
        ];

        $response = $this->postJson(route('webhooks.whatsapp', $this->businessA->id), $payload);
        $response->assertStatus(200);

        // Store Alpha
        $this->assertDatabaseHas('messages', [
            'platform_message_id' => 'WA_OUTBOUND_001',
            'sender_type' => 'agent',
            'body' => 'أهلاً بك، تم شحن طلبك!',
        ]);

        $convA = Conversation::where('business_id', $this->businessA->id)->first();
        $this->assertEquals(0, $convA->unread_count);

        // Store Beta
        $convB = Conversation::where('business_id', $this->businessB->id)->first();
        $this->assertEquals(0, $convB->unread_count);
    }

    public function test_conversation_can_be_transferred_between_stores(): void
    {
        $contact = Contact::create([
            'business_id' => $this->businessA->id,
            'name' => 'Customer Z',
            'phone' => '201122334455',
            'platform' => 'whatsapp',
            'platform_sender_id' => '201122334455',
        ]);

        $conversation = Conversation::create([
            'business_id' => $this->businessA->id,
            'contact_id' => $contact->id,
            'platform' => 'whatsapp',
            'status' => 'open',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);

        $this->actingAs($this->user);
        session(['current_business_id' => $this->businessA->id]);

        $response = $this->postJson(route('inbox.transfer', $conversation->id), [
            'target_business_id' => $this->businessB->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $conversation->refresh();
        $this->assertEquals($this->businessB->id, $conversation->business_id);
    }

    public function test_owner_can_send_message_even_if_session_has_different_business_id(): void
    {
        $contact = Contact::create([
            'business_id' => $this->businessA->id,
            'name' => 'Customer Multi',
            'phone' => '201122334466',
            'platform' => 'whatsapp',
            'platform_sender_id' => '201122334466',
        ]);

        $conversation = Conversation::create([
            'business_id' => $this->businessA->id,
            'contact_id' => $contact->id,
            'platform' => 'whatsapp',
            'status' => 'closed',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);

        // Acting as owner, but session currently points to Store Beta (businessB)
        $this->actingAs($this->user);
        session(['current_business_id' => $this->businessB->id]);

        $response = $this->postJson(route('inbox.send', $conversation->id), [
            'body' => 'مرحباً، هذه رسالة تجريبية من المتجر الأول',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $conversation->refresh();
        $this->assertEquals('open', $conversation->status);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'body' => 'مرحباً، هذه رسالة تجريبية من المتجر الأول',
            'sender_type' => 'agent',
        ]);
    }
}
