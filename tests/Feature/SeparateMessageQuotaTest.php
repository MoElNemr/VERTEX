<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MessageQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeparateMessageQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_and_subscription_have_independent_ai_and_regular_quotas(): void
    {
        $plan = Plan::create([
            'name' => 'الباقة الاحترافية',
            'type' => 'standard',
            'monthly_message_quota' => 1000,
            'monthly_ai_quota' => 200,
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        $user = User::factory()->unpaid()->create();
        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'messages_used' => 0,
            'ai_messages_used' => 0,
        ]);

        $this->assertEquals(1000, $sub->messages_limit);
        $this->assertEquals(200, $sub->ai_messages_limit);
        $this->assertEquals(0, $sub->messages_used);
        $this->assertEquals(0, $sub->ai_messages_used);
    }

    public function test_regular_message_consumption_only_increments_regular_quota(): void
    {
        $plan = Plan::create([
            'name' => 'الباقة الأساسية',
            'type' => 'standard',
            'monthly_message_quota' => 50,
            'monthly_ai_quota' => 20,
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        $user = User::factory()->unpaid()->create();
        $business = Business::create(['name' => 'متجر تجريبي', 'user_id' => $user->id]);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'messages_limit' => 50,
            'messages_used' => 0,
            'ai_messages_limit' => 20,
            'ai_messages_used' => 0,
            'quota_resets_at' => now()->addMonth(),
        ]);

        $quotaService = app(MessageQuotaService::class);

        $this->assertTrue($quotaService->canSend($user, 1, false));
        $this->assertTrue($quotaService->canSendAi($user, 1));

        // Consume regular message
        $consumed = $quotaService->consumeQuota($user, $business->id, 'whatsapp', 1, 'Manual reply', false);
        $this->assertTrue($consumed);

        $sub->refresh();
        $this->assertEquals(1, $sub->messages_used);
        $this->assertEquals(0, $sub->ai_messages_used); // AI quota untouched!
    }

    public function test_ai_message_consumption_only_increments_ai_quota(): void
    {
        $plan = Plan::create([
            'name' => 'الباقة الأساسية',
            'type' => 'standard',
            'monthly_message_quota' => 50,
            'monthly_ai_quota' => 10,
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        $user = User::factory()->unpaid()->create();
        $business = Business::create(['name' => 'متجر تجريبي', 'user_id' => $user->id]);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'messages_limit' => 50,
            'messages_used' => 5,
            'ai_messages_limit' => 10,
            'ai_messages_used' => 0,
            'quota_resets_at' => now()->addMonth(),
        ]);

        $quotaService = app(MessageQuotaService::class);

        // Consume AI message
        $consumed = $quotaService->consumeQuota($user, $business->id, 'instagram', 1, 'Groq reply', true);
        $this->assertTrue($consumed);

        $sub->refresh();
        $this->assertEquals(5, $sub->messages_used); // Regular quota untouched!
        $this->assertEquals(1, $sub->ai_messages_used);
    }

    public function test_ai_quota_exhaustion_does_not_block_regular_messages_and_vice_versa(): void
    {
        $plan = Plan::create([
            'name' => 'الباقة الأساسية',
            'type' => 'standard',
            'monthly_message_quota' => 100,
            'monthly_ai_quota' => 5,
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        $user = User::factory()->unpaid()->create();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'messages_limit' => 100,
            'messages_used' => 10,
            'ai_messages_limit' => 5,
            'ai_messages_used' => 5, // AI quota exhausted!
            'quota_resets_at' => now()->addMonth(),
        ]);

        $quotaService = app(MessageQuotaService::class);

        // AI is blocked
        $this->assertFalse($quotaService->canSendAi($user, 1));

        // Regular message can still be sent!
        $this->assertTrue($quotaService->canSend($user, 1, false));
    }
}
