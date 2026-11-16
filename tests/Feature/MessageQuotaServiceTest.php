<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Plan;
use App\Models\User;
use App\Services\MessageQuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MessageQuotaService $quotaService;

    protected User $user;

    protected Business $business;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotaService = app(MessageQuotaService::class);

        $this->plan = Plan::create([
            'name' => 'Pro Plan',
            'type' => 'standard',
            'price_monthly' => 49.00,
            'price_yearly' => 490.00,
            'monthly_message_quota' => 1000,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Quota Test Store',
        ]);
    }

    public function test_can_send_returns_true_when_subscription_has_available_quota(): void
    {
        $this->user->subscriptions()->update([
            'messages_limit' => 500,
            'messages_used' => 100,
            'quota_resets_at' => Carbon::now()->addMonth(),
        ]);

        $this->assertTrue($this->quotaService->canSend($this->user, 50));
        $this->assertTrue($this->quotaService->canSend($this->user, 400));
        $this->assertFalse($this->quotaService->canSend($this->user, 401));
    }

    public function test_consume_quota_deducts_from_subscription_quota(): void
    {
        $this->user->subscriptions()->update([
            'messages_limit' => 500,
            'messages_used' => 0,
            'quota_resets_at' => Carbon::now()->addMonth(),
        ]);

        $consumed = $this->quotaService->consumeQuota(
            $this->user,
            $this->business->id,
            'whatsapp',
            5,
            'Sent 5 messages'
        );

        $this->assertTrue($consumed);

        $sub = $this->user->activeSubscription->fresh();
        $this->assertEquals(5, $sub->messages_used);
    }

    public function test_cannot_send_when_quota_exhausted(): void
    {
        $this->user->subscriptions()->update([
            'messages_limit' => 100,
            'messages_used' => 100,
            'quota_resets_at' => Carbon::now()->addMonth(),
        ]);

        $this->assertFalse($this->quotaService->canSend($this->user, 1));

        $consumed = $this->quotaService->consumeQuota(
            $this->user,
            $this->business->id,
            'whatsapp',
            1,
            'Exhausted send attempt'
        );

        $this->assertFalse($consumed);
    }

    public function test_monthly_reset_refreshes_messages_used_when_reset_date_is_past(): void
    {
        // Quota was fully used, but reset date was yesterday
        $this->user->subscriptions()->update([
            'messages_limit' => 500,
            'messages_used' => 500,
            'quota_resets_at' => Carbon::now()->subDay(),
        ]);

        $this->assertTrue($this->quotaService->canSend($this->user, 10));

        $sub = $this->user->activeSubscription->fresh();
        $this->assertEquals(0, $sub->messages_used);
        $this->assertTrue($sub->quota_resets_at->isFuture());
    }

    public function test_super_admin_bypasses_all_quota_limits(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
        ]);
        $admin->subscriptions()->update([
            'messages_limit' => 0,
            'messages_used' => 0,
        ]);

        $this->assertTrue($this->quotaService->canSend($admin, 100000));

        $consumed = $this->quotaService->consumeQuota(
            $admin,
            $this->business->id,
            'whatsapp',
            500,
            'Super admin free send'
        );

        $this->assertTrue($consumed);
    }

    public function test_unlimited_plan_allows_sending(): void
    {
        $this->user->subscriptions()->update([
            'messages_limit' => null,
            'messages_used' => 5000,
            'quota_resets_at' => Carbon::now()->addMonth(),
        ]);

        $this->assertTrue($this->quotaService->canSend($this->user, 1000));

        $consumed = $this->quotaService->consumeQuota(
            $this->user,
            $this->business->id,
            'whatsapp',
            10,
            'Unlimited send'
        );

        $this->assertTrue($consumed);
        $sub = $this->user->activeSubscription->fresh();
        $this->assertEquals(5010, $sub->messages_used);
    }

    public function test_get_quota_summary_returns_accurate_metrics(): void
    {
        $this->user->subscriptions()->update([
            'messages_limit' => 1000,
            'messages_used' => 250,
            'quota_resets_at' => Carbon::now()->addDays(15),
        ]);

        $summary = $this->quotaService->getQuotaSummary($this->user);

        $this->assertEquals(1000, $summary['limit']);
        $this->assertEquals(250, $summary['used']);
        $this->assertEquals(750, $summary['remaining']);
        $this->assertFalse($summary['is_unlimited']);
    }

    public function test_user_without_subscription_cannot_send(): void
    {
        $noSubUser = User::factory()->create();
        $noSubUser->subscriptions()->delete();

        $this->assertFalse($this->quotaService->canSend($noSubUser, 1));
        $this->assertFalse($this->quotaService->consumeQuota($noSubUser, $this->business->id, 'whatsapp', 1));
    }
}
