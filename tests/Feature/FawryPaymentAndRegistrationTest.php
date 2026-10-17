<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FawryPaymentAndRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'name' => 'Standard Unlimited',
            'type' => 'standard',
            'price_monthly' => 0.00,
            'price_yearly' => 0.00,
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Premium Unlimited',
            'type' => 'premium',
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'is_active' => true,
        ]);
    }

    public function test_registration_screen_renders_with_plans(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('plans', 2)
        );
    }

    public function test_user_can_register_with_free_trial_plan(): void
    {
        $standardPlan = Plan::where('type', 'standard')->first();

        $response = $this->post('/register', [
            'name' => 'Tarek Ahmed',
            'email' => 'tarek@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_id' => $standardPlan->id,
            'billing_cycle' => 'monthly',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'tarek@example.com')->first();
        $this->assertNotNull($user);

        // Check active trial subscription created
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $standardPlan->id,
            'status' => 'trial',
        ]);

        $response->assertRedirect(route('businesses.index'));
    }

    public function test_user_can_register_with_paid_plan_and_fawry_charge(): void
    {
        $premiumPlan = Plan::where('type', 'premium')->first();

        $response = $this->post('/register', [
            'name' => 'Khaled Omar',
            'email' => 'khaled@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_id' => $premiumPlan->id,
            'billing_cycle' => 'monthly',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'khaled@example.com')->first();
        $this->assertNotNull($user);

        // Check payment transaction generated
        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $user->id,
            'plan_id' => $premiumPlan->id,
            'status' => 'pending',
            'amount' => 99.00,
            'payment_method' => 'fawry',
        ]);

        $response->assertRedirect(route('subscription.index'));
    }

    public function test_fawry_ipn_webhook_activates_subscription_on_payment(): void
    {
        $user = User::factory()->create();
        $premiumPlan = Plan::where('type', 'premium')->first();

        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $premiumPlan->id,
            'reference_number' => 'SUB_TEST_123',
            'fawry_reference_number' => '987654321',
            'amount' => 99.00,
            'currency' => 'EGP',
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'payment_method' => 'fawry',
        ]);

        $payload = [
            'merchantRefNum' => 'SUB_TEST_123',
            'fawryRefNumber' => '987654321',
            'orderStatus' => 'PAID',
            'paymentAmount' => 99.00,
        ];

        $response = $this->postJson('/webhooks/fawry', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Assert transaction marked paid
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $transaction->id,
            'status' => 'paid',
        ]);

        // Assert user subscription activated
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $premiumPlan->id,
            'status' => 'active',
        ]);
    }

    public function test_superadmin_can_update_fawry_settings(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/fawry/settings', [
            'merchant_code' => 'TEST_CODE_999',
            'security_key' => 'SECRET_KEY_123',
            'merchant_wallet' => '01009998887',
            'mode' => 'sandbox',
        ]);

        $response->assertRedirect();

        $this->assertEquals('TEST_CODE_999', SystemSetting::get('fawry_merchant_code'));
        $this->assertEquals('SECRET_KEY_123', SystemSetting::get('fawry_security_key'));
        $this->assertEquals('01009998887', SystemSetting::get('fawry_merchant_wallet'));
        $this->assertEquals('sandbox', SystemSetting::get('fawry_mode'));
    }

    public function test_user_can_access_subscription_page_and_checkout(): void
    {
        $user = User::factory()->create();
        $premiumPlan = Plan::where('type', 'premium')->first();

        // Access index
        $indexResponse = $this->actingAs($user)->get('/subscription');
        $indexResponse->assertStatus(200);
        $indexResponse->assertInertia(fn ($page) => $page->component('Subscription/Index'));

        // Checkout plan
        $checkoutResponse = $this->actingAs($user)->postJson('/subscription/checkout', [
            'plan_id' => $premiumPlan->id,
            'billing_cycle' => 'yearly',
        ]);

        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertJsonStructure([
            'status',
            'charge' => [
                'reference_number',
                'fawry_reference_number',
                'amount',
                'currency',
            ],
        ]);
    }
}
