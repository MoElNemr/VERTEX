<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'name' => 'Contacts Hub Store',
        ]);

        $this->contact = Contact::create([
            'business_id' => $this->business->id,
            'name' => 'Mahmoud Hassan',
            'phone' => '+201099887766',
            'email' => 'mahmoud@example.com',
            'platform' => 'whatsapp',
            'platform_sender_id' => '201099887766@s.whatsapp.net',
            'tags' => ['VIP', 'عميل قديم'],
            'notes' => 'يفضل التواصل بعد الظهر.',
        ]);
    }

    public function test_user_can_view_contacts_index(): void
    {
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->get(route('contacts.index'));

        $res->assertOk();
    }

    public function test_contacts_search_filters_results(): void
    {
        Contact::create([
            'business_id' => $this->business->id,
            'name' => 'Khaled Zaki',
            'phone' => '+201122334455',
            'platform' => 'telegram',
            'platform_sender_id' => '998811',
        ]);

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->get(route('contacts.index', ['search' => 'Mahmoud']));

        $res->assertOk();
    }

    public function test_user_can_view_contact_details(): void
    {
        Conversation::create([
            'business_id' => $this->business->id,
            'contact_id' => $this->contact->id,
            'platform' => 'whatsapp',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->getJson(route('contacts.show', $this->contact->id));

        $res->assertOk()
            ->assertJsonPath('contact.name', 'Mahmoud Hassan')
            ->assertJsonPath('contact.conversations_count', 1);
    }

    public function test_user_can_update_contact(): void
    {
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->patchJson(route('contacts.update', $this->contact->id), [
                'name' => 'Mahmoud H. Ali',
                'phone' => '+201099887700',
                'email' => 'updated@example.com',
                'notes' => 'تم تغيير رقم الهاتف الرئيسي',
                'tags' => ['VIP', 'مهتم_بالعروض'],
            ]);

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('contact.name', 'Mahmoud H. Ali');

        $this->assertDatabaseHas('contacts', [
            'id' => $this->contact->id,
            'name' => 'Mahmoud H. Ali',
            'email' => 'updated@example.com',
            'notes' => 'تم تغيير رقم الهاتف الرئيسي',
        ]);
    }

    public function test_user_can_export_contacts_as_csv(): void
    {
        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->get(route('contacts.export'));

        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', $res->headers->get('Content-Disposition'));
    }
}
