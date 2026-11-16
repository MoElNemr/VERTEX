<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAiSetting;
use App\Models\FacebookAccount;
use App\Models\InstagramAccount;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AutoReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommentAutoReplyModesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_comment_reply_mode_to_ai(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['name' => 'متجر الموضة', 'user_id' => $user->id]);

        $fb = FacebookAccount::create([
            'business_id' => $business->id,
            'page_id' => 'fb_page_123',
            'page_name' => 'صفحة الموضة',
            'page_access_token' => 'token_123',
            'comment_reply_enabled' => false,
            'comment_reply_mode' => 'fixed',
        ]);

        $response = $this->actingAs($user)->post(route('channels.comment-settings.update', $business->id), [
            'platform' => 'facebook',
            'comment_reply_enabled' => true,
            'comment_reply_mode' => 'ai',
            'comment_reply_text' => 'تم الرد',
            'dm_message_text' => 'مرحباً بك',
        ]);

        $response->assertRedirect();
        $fb->refresh();
        $this->assertTrue($fb->comment_reply_enabled);
        $this->assertEquals('ai', $fb->comment_reply_mode);
    }

    public function test_inbound_comment_with_ai_mode_triggers_groq_ai_and_consumes_ai_quota(): void
    {
        SystemSetting::set('groq_api_key', 'gsk_test_comment_key', 'groq');
        SystemSetting::set('groq_enabled', true, 'groq');

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'أهلاً سارة! السعر 350 جنيه، راجعي رسائلك الخاصة 📩']],
                ],
            ], 200),
        ]);

        $user = User::factory()->unpaid()->create();
        $business = Business::create(['name' => 'متجر العطور', 'user_id' => $user->id]);

        $plan = Plan::create([
            'name' => 'الباقة الأساسية',
            'type' => 'standard',
            'monthly_message_quota' => 100,
            'monthly_ai_quota' => 20,
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'messages_limit' => 100,
            'messages_used' => 0,
            'ai_messages_limit' => 20,
            'ai_messages_used' => 0,
            'quota_resets_at' => now()->addMonth(),
        ]);

        InstagramAccount::create([
            'business_id' => $business->id,
            'instagram_business_id' => 'ig_12345',
            'username' => 'perfume_store',
            'access_token' => 'token_xyz',
            'comment_reply_enabled' => true,
            'comment_reply_mode' => 'ai',
            'dm_message_text' => 'شكراً لتواصلك يا {name}!',
        ]);

        BusinessAiSetting::create([
            'business_id' => $business->id,
            'is_enabled' => true,
            'ai_enabled' => true,
            'ai_personality' => 'راقي ومهذب',
        ]);

        $publicSentText = null;
        $privateSentText = null;

        $autoReply = app(AutoReplyService::class);
        $result = $autoReply->handleInboundComment(
            $business,
            'instagram',
            'cmt_777',
            'بكام العطر ده؟',
            'user_999',
            'سارة',
            function ($text) use (&$publicSentText) {
                $publicSentText = $text;

                return true;
            },
            function ($text) use (&$privateSentText) {
                $privateSentText = $text;

                return true;
            }
        );

        $this->assertTrue($result);
        $this->assertEquals('أهلاً سارة! السعر 350 جنيه، راجعي رسائلك الخاصة 📩', $publicSentText);
        $this->assertEquals('شكراً لتواصلك يا سارة!', $privateSentText);

        $sub->refresh();
        $this->assertEquals(0, $sub->messages_used); // Regular quota untouched!
        $this->assertEquals(1, $sub->ai_messages_used); // AI quota consumed!
    }
}
