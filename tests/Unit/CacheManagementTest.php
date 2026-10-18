<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\WhatsappAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_setting_caches_and_invalidates_on_update(): void
    {
        Cache::flush();

        // 1. Initial set
        SystemSetting::set('test_cache_key', 'initial_value');

        // 2. First get caches the value
        $val1 = SystemSetting::get('test_cache_key');
        $this->assertEquals('initial_value', $val1);
        $this->assertTrue(Cache::has('system_setting_test_cache_key'));

        // 3. Update setting should delete cache immediately
        SystemSetting::set('test_cache_key', 'updated_value');
        $this->assertFalse(Cache::has('system_setting_test_cache_key'));

        // 4. Next get returns updated value and re-caches
        $val2 = SystemSetting::get('test_cache_key');
        $this->assertEquals('updated_value', $val2);
        $this->assertTrue(Cache::has('system_setting_test_cache_key'));
    }

    public function test_plan_invalidates_cache_on_save_and_delete(): void
    {
        Cache::flush();

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'type' => 'standard',
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        $plans = Plan::getActivePlans();
        $this->assertCount(1, $plans);
        $this->assertTrue(Cache::has('active_pricing_plans'));

        // Update plan triggers cache invalidation
        $plan->update(['name' => 'Pro Plan Updated']);
        $this->assertFalse(Cache::has('active_pricing_plans'));

        // Delete plan triggers cache invalidation
        Plan::getActivePlans();
        $this->assertTrue(Cache::has('active_pricing_plans'));
        $plan->delete();
        $this->assertFalse(Cache::has('active_pricing_plans'));
    }
}
