<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'name' => 'Analytics Test Store',
        ]);
    }

    public function test_dashboard_renders_with_correct_metrics(): void
    {
        $contact = Contact::create([
            'business_id' => $this->business->id,
            'name' => 'Client One',
            'platform' => 'whatsapp',
            'platform_sender_id' => '11111',
        ]);

        $conv1 = Conversation::create([
            'business_id' => $this->business->id,
            'contact_id' => $contact->id,
            'platform' => 'whatsapp',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conv1->id,
            'sender_type' => 'contact',
            'body' => 'مرحبا',
            'status' => 'delivered',
            'created_at' => now(),
        ]);

        $agent = TeamMember::create([
            'user_id' => $this->owner->id,
            'name' => 'Agent Nour',
            'email' => 'nour@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
            'is_active' => true,
        ]);
        $this->business->teamMembers()->attach($agent->id, ['role' => 'agent']);
        $conv1->update(['assigned_to' => $agent->id]);

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->get(route('dashboard'));

        $res->assertOk();
        $page = $res->viewData('page');
        $this->assertEquals('Analytics Test Store', $page['props']['business']['name']);
        $this->assertEquals(1, $page['props']['stats']['total_conversations']);
        $this->assertEquals(1, $page['props']['stats']['open']);
        $this->assertEquals(1, $page['props']['stats']['messages_today']);
        $this->assertCount(1, $page['props']['teamPerformance']);
        $this->assertEquals('Agent Nour', $page['props']['teamPerformance'][0]['name']);
        $this->assertEquals(1, $page['props']['teamPerformance'][0]['handled']);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
