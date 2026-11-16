<?php

namespace Tests\Feature;

use App\Models\AutoReplyRule;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AutoReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AutoReplyPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Conversation $conversation;

    protected Contact $contact;

    protected AutoReplyService $autoReplyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Auto Reply Test Store',
        ]);

        $this->contact = Contact::create([
            'business_id' => $this->business->id,
            'platform' => 'whatsapp',
            'platform_sender_id' => '+201012345678',
            'name' => 'Test Customer',
        ]);

        $this->conversation = Conversation::create([
            'business_id' => $this->business->id,
            'contact_id' => $this->contact->id,
            'platform' => 'whatsapp',
            'status' => 'open',
        ]);

        $this->autoReplyService = app(AutoReplyService::class);
    }

    public function test_welcome_rule_triggers_on_first_customer_message(): void
    {
        AutoReplyRule::create([
            'business_id' => $this->business->id,
            'name' => 'Welcome Rule',
            'platform' => 'all',
            'trigger_type' => 'welcome',
            'match_type' => 'contains',
            'reply_text' => 'أهلاً بك في متجرنا! كيف يمكننا مساعدتك اليوم؟',
            'priority' => 1,
            'is_active' => true,
        ]);

        $inbound = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'type' => 'text',
            'body' => 'مرحبا',
            'status' => 'delivered',
        ]);

        $reply = $this->autoReplyService->handleInboundDirectMessage($this->conversation, $inbound);

        $this->assertNotNull($reply);
        $this->assertEquals('أهلاً بك في متجرنا! كيف يمكننا مساعدتك اليوم؟', $reply->body);
        $this->assertEquals('bot', $reply->sender_type);
    }

    public function test_keyword_matching_exact(): void
    {
        AutoReplyRule::create([
            'business_id' => $this->business->id,
            'name' => 'Pricing Exact',
            'platform' => 'whatsapp',
            'trigger_type' => 'keyword',
            'match_type' => 'exact',
            'keywords' => ['الأسعار', 'قائمة الاسعار'],
            'reply_text' => 'تفضل قائمة الأسعار تبدأ من 150 ج.م',
            'priority' => 10,
            'is_active' => true,
        ]);

        // Prior messages exist so welcome doesn't fire
        Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'رسالة قديمة 1',
        ]);
        Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'رسالة قديمة 2',
        ]);

        // Non-exact match should NOT trigger
        $inboundMismatch = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'ممكن الأسعار لو سمحت؟',
        ]);
        $replyMismatch = $this->autoReplyService->handleInboundDirectMessage($this->conversation, $inboundMismatch);
        $this->assertNull($replyMismatch);

        // Exact match triggers
        $inboundExact = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'الأسعار',
        ]);
        $replyExact = $this->autoReplyService->handleInboundDirectMessage($this->conversation, $inboundExact);
        $this->assertNotNull($replyExact);
        $this->assertEquals('تفضل قائمة الأسعار تبدأ من 150 ج.م', $replyExact->body);
    }

    public function test_keyword_matching_contains_and_starts_with(): void
    {
        AutoReplyRule::create([
            'business_id' => $this->business->id,
            'name' => 'Shipping Starts With',
            'platform' => 'whatsapp',
            'trigger_type' => 'keyword',
            'match_type' => 'starts_with',
            'keywords' => ['الشحن', 'توصيل'],
            'reply_text' => 'الشحن لجميع المحافظات خلال 48 ساعة.',
            'priority' => 5,
            'is_active' => true,
        ]);

        AutoReplyRule::create([
            'business_id' => $this->business->id,
            'name' => 'Discount Contains',
            'platform' => 'whatsapp',
            'trigger_type' => 'keyword',
            'match_type' => 'contains',
            'keywords' => ['خصم', 'كوبون', 'بروموكود'],
            'reply_text' => 'استخدم كود SAVE10 للحصول على خصم 10%!',
            'priority' => 8,
            'is_active' => true,
        ]);

        // Seed 2 existing messages
        Message::create(['conversation_id' => $this->conversation->id, 'sender_type' => 'contact', 'body' => 'c1']);
        Message::create(['conversation_id' => $this->conversation->id, 'sender_type' => 'contact', 'body' => 'c2']);

        // Test starts_with
        $inboundStarts = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'الشحن بياخد كام يوم للقاهرة؟',
        ]);
        $replyStarts = $this->autoReplyService->handleInboundDirectMessage($this->conversation, $inboundStarts);
        $this->assertNotNull($replyStarts);
        $this->assertEquals('الشحن لجميع المحافظات خلال 48 ساعة.', $replyStarts->body);

        // Test contains
        $inboundContains = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'هل فيه أي خصم متاح على الطلبات الكبيرة؟',
        ]);
        $replyContains = $this->autoReplyService->handleInboundDirectMessage($this->conversation, $inboundContains);
        $this->assertNotNull($replyContains);
        $this->assertEquals('استخدم كود SAVE10 للحصول على خصم 10%!', $replyContains->body);
    }

    public function test_priority_ordering_selects_lowest_priority_number_first(): void
    {
        AutoReplyRule::create([
            'business_id' => $this->business->id,
            'name' => 'General Support',
            'platform' => 'all',
            'trigger_type' => 'keyword',
            'match_type' => 'contains',
            'keywords' => ['مساعدة'],
            'reply_text' => 'قاعدة الأولوية المنخفضة (رقم 20)',
            'priority' => 20,
            'is_active' => true,
        ]);

        AutoReplyRule::create([
            'business_id' => $this->business->id,
            'name' => 'Urgent Support',
            'platform' => 'all',
            'trigger_type' => 'keyword',
            'match_type' => 'contains',
            'keywords' => ['مساعدة'],
            'reply_text' => 'قاعدة الأولوية العالية (رقم 2)',
            'priority' => 2,
            'is_active' => true,
        ]);

        Message::create(['conversation_id' => $this->conversation->id, 'sender_type' => 'contact', 'body' => 'c1']);
        Message::create(['conversation_id' => $this->conversation->id, 'sender_type' => 'contact', 'body' => 'c2']);

        $inbound = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'محتاج مساعدة سريعة',
        ]);

        $reply = $this->autoReplyService->handleInboundDirectMessage($this->conversation, $inbound);

        $this->assertNotNull($reply);
        $this->assertEquals('قاعدة الأولوية العالية (رقم 2)', $reply->body);
    }

    public function test_fallback_rule_cooldown_prevents_spam(): void
    {
        Cache::flush();

        AutoReplyRule::create([
            'business_id' => $this->business->id,
            'name' => 'Fallback Rule',
            'platform' => 'all',
            'trigger_type' => 'fallback',
            'match_type' => 'contains',
            'reply_text' => 'فريقنا حالياً غير متاح، وسنرد عليك قريباً.',
            'priority' => 99,
            'is_active' => true,
        ]);

        Message::create(['conversation_id' => $this->conversation->id, 'sender_type' => 'contact', 'body' => 'c1']);
        Message::create(['conversation_id' => $this->conversation->id, 'sender_type' => 'contact', 'body' => 'c2']);

        // Unrecognized message 1 -> triggers fallback
        $inbound1 = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'سؤال عشوائي جداً لا يطابق أي كلمة',
        ]);
        $reply1 = $this->autoReplyService->handleInboundDirectMessage($this->conversation, $inbound1);
        $this->assertNotNull($reply1);
        $this->assertEquals('فريقنا حالياً غير متاح، وسنرد عليك قريباً.', $reply1->body);

        // Immediate next unrecognized message 2 -> cooldown in effect -> null
        $inbound2 = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'contact',
            'body' => 'سؤال عشوائي آخر في نفس اللحظة',
        ]);
        $reply2 = $this->autoReplyService->handleInboundDirectMessage($this->conversation, $inbound2);
        $this->assertNull($reply2);
    }

    public function test_auto_reply_rules_crud_via_channel_controller(): void
    {
        // 1. Store rule
        $response = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->post(route('channels.auto-reply-rules.store', $this->business->id), [
                'name' => 'Refund Policy',
                'platform' => 'all',
                'trigger_type' => 'keyword',
                'match_type' => 'contains',
                'keywords' => 'استرجاع, استبدال, ترجيع',
                'reply_text' => 'الاسترجاع متاح خلال 14 يوماً مع الفاتورة الأصلية.',
                'priority' => 3,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('auto_reply_rules', [
            'business_id' => $this->business->id,
            'name' => 'Refund Policy',
            'priority' => 3,
        ]);

        $rule = AutoReplyRule::where('name', 'Refund Policy')->first();
        $this->assertEquals(['استرجاع', 'استبدال', 'ترجيع'], $rule->keywords);

        // 2. Toggle rule active state
        $toggleResponse = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->patchJson(route('channels.auto-reply-rules.toggle', [$this->business->id, $rule->id]));

        $toggleResponse->assertOk();
        $this->assertFalse($rule->fresh()->is_active);

        // 3. Update rule
        $updateResponse = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->put(route('channels.auto-reply-rules.update', [$this->business->id, $rule->id]), [
                'name' => 'Refund Policy V2',
                'platform' => 'tiktok',
                'trigger_type' => 'keyword',
                'match_type' => 'exact',
                'keywords' => 'استرجاع فوري',
                'reply_text' => 'تحديث نص الاسترجاع.',
                'priority' => 1,
            ]);

        $updateResponse->assertRedirect();
        $this->assertEquals('Refund Policy V2', $rule->fresh()->name);
        $this->assertEquals('tiktok', $rule->fresh()->platform);

        // 4. Delete rule
        $deleteResponse = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->delete(route('channels.auto-reply-rules.destroy', [$this->business->id, $rule->id]));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('auto_reply_rules', ['id' => $rule->id]);
    }
}
