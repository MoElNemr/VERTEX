<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerA;

    protected User $ownerB;

    protected Business $businessA;

    protected Business $businessB;

    protected Contact $contactB;

    protected Conversation $conversationB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerA = User::factory()->create();
        $this->businessA = Business::create([
            'user_id' => $this->ownerA->id,
            'name' => 'Store Alpha',
        ]);

        $this->ownerB = User::factory()->create();
        $this->businessB = Business::create([
            'user_id' => $this->ownerB->id,
            'name' => 'Store Beta',
        ]);

        $this->contactB = Contact::create([
            'business_id' => $this->businessB->id,
            'name' => 'Customer of Store Beta',
            'platform' => 'whatsapp',
            'platform_sender_id' => '999888777',
        ]);

        $this->conversationB = Conversation::create([
            'business_id' => $this->businessB->id,
            'contact_id' => $this->contactB->id,
            'platform' => 'whatsapp',
            'status' => 'open',
            'last_message_at' => now(),
        ]);
    }

    public function test_merchant_cannot_read_messages_of_another_business(): void
    {
        // Merchant A tries to access conversation belonging to Store B
        $res = $this->actingAs($this->ownerA)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->getJson(route('inbox.messages', $this->conversationB->id));

        $res->assertStatus(403);
    }

    public function test_merchant_cannot_send_messages_to_another_business_conversation(): void
    {
        $res = $this->actingAs($this->ownerA)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->postJson(route('inbox.send', $this->conversationB->id), [
                'body' => 'حياك الله من متجر ألفا',
            ]);

        $res->assertStatus(403);
    }

    public function test_merchant_cannot_access_or_update_contact_of_another_business(): void
    {
        $res = $this->actingAs($this->ownerA)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->getJson(route('contacts.show', $this->contactB->id));

        $res->assertStatus(403);

        $updateRes = $this->actingAs($this->ownerA)
            ->withSession(['current_business_id' => $this->businessA->id])
            ->patchJson(route('contacts.update', $this->contactB->id), [
                'name' => 'Hacked Name',
            ]);

        $updateRes->assertStatus(403);
    }

    public function test_merchant_cannot_access_ai_settings_of_another_business(): void
    {
        $res = $this->actingAs($this->ownerA)
            ->get(route('ai-settings.show', $this->businessB->id));

        $res->assertStatus(403);

        $updateRes = $this->actingAs($this->ownerA)
            ->post(route('ai-settings.update', $this->businessB->id), [
                'ai_enabled' => true,
            ]);

        $updateRes->assertStatus(403);
    }
}
