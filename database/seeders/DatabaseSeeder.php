<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. إنشاء الباقات
        $standardPlan = Plan::create([
            'name' => 'Standard Unlimited',
            'type' => 'standard',
            'max_businesses' => null, // غير محدود
            'price_monthly' => 29.00,
            'price_yearly' => 290.00,
            'is_active' => true,
        ]);

        $premiumPlan = Plan::create([
            'name' => 'Premium Unlimited',
            'type' => 'premium',
            'max_businesses' => null,
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'is_active' => true,
        ]);

        // 2. إنشاء حسابك الرئيسي
        $admin = User::create([
            'name' => 'Mohamed Nemr',
            'email' => 'admin@vertex.app',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // 3. تفعيل اشتراك غير محدود لك
        Subscription::create([
            'user_id' => $admin->id,
            'plan_id' => $premiumPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYears(10),
        ]);

        // 4. إنشاء بيزنس تجريبي رئيسي
        Business::create([
            'user_id' => $admin->id,
            'name' => 'Vertex Hub',
            'logo' => null,
        ]);
    }
}
