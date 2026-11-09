<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAiSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Business $business;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'name' => 'AI Test Store',
        ]);

        $contact = Contact::create([
            'business_id' => $this->business->id,
            'name' => 'Omar Customer',
            'platform' => 'telegram',
            'platform_sender_id' => '12345678',
        ]);

        $this->conversation = Conversation::create([
            'business_id' => $this->business->id,
            'contact_id' => $contact->id,
            'platform' => 'telegram',
            'status' => 'open',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);
    }

    public function test_owner_can_view_and_update_ai_settings(): void
    {
        $this->actingAs($this->owner)
            ->get(route('ai-settings.show', $this->business->id))
            ->assertOk();

        $this->actingAs($this->owner)
            ->post(route('ai-settings.update', $this->business->id), [
                'ai_enabled' => true,
                'ai_model' => 'gpt-4o',
                'ai_personality' => 'ودود ومحترف للغاية',
                'ai_instructions' => 'التوصيل متاح يومياً',
                'working_hours_only' => true,
                'work_start_hour' => 10,
                'work_end_hour' => 20,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('business_ai_settings', [
            'business_id' => $this->business->id,
            'ai_enabled' => true,
            'ai_model' => 'gpt-4o',
            'working_hours_only' => true,
            'work_start_hour' => 10,
            'work_end_hour' => 20,
        ]);
    }

    public function test_ai_suggest_reply_returns_suggestion(): void
    {
        BusinessAiSetting::create([
            'business_id' => $this->business->id,
            'ai_enabled' => true,
            'ai_model' => 'gpt-4o-mini',
        ]);

        $this->mock(AiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('suggestReply')
                ->once()
                ->andReturn('أهلاً بك! طلبك قيد التجهيز وسيصلك خلال 24 ساعة.');
        });

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->postJson(route('inbox.ai-suggest', $this->conversation->id));

        $res->assertOk()
            ->assertJson([
                'suggestion' => 'أهلاً بك! طلبك قيد التجهيز وسيصلك خلال 24 ساعة.',
            ]);
    }

    public function test_ai_suggest_reply_fails_when_ai_is_disabled(): void
    {
        BusinessAiSetting::create([
            'business_id' => $this->business->id,
            'ai_enabled' => false,
        ]);

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->postJson(route('inbox.ai-suggest', $this->conversation->id));

        $res->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    public function test_ai_summarize_conversation_returns_summary(): void
    {
        $this->mock(AiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('summarize')
                ->once()
                ->andReturn('استفسر العميل عن موعد الشحن وتم الرد بالموعد المحدد.');
        });

        $res = $this->actingAs($this->owner)
            ->withSession(['current_business_id' => $this->business->id])
            ->postJson(route('inbox.summarize', $this->conversation->id));

        $res->assertOk()
            ->assertJson([
                'summary' => 'استفسر العميل عن موعد الشحن وتم الرد بالموعد المحدد.',
            ]);
    }
}
