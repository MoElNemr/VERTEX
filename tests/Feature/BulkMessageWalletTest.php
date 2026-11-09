<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkMessageWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'name' => 'Standard Plan',
            'type' => 'standard',
            'price_monthly' => 19.00,
            'price_yearly' => 190.00,
            'is_active' => true,
        ]);

        SystemSetting::set('fawry_merchant_code', 'TEST_MERCHANT_101');
        SystemSetting::set('fawry_security_key', 'test-secret-key-123');
        SystemSetting::set('fawry_merchant_wallet', '01099887766');
        SystemSetting::set('fawry_mode', 'sandbox');
    }

    public function test_user_can_view_wallet_page_and_default_packages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('wallet.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Wallet/Index')
            ->has('wallet')
            ->has('packages')
            ->has('transactions')
            ->has('fawryConfig')
        );

        $this->assertDatabaseHas('bulk_message_wallets', [
            'user_id' => $user->id,
            'balance' => 0,
            'credits' => 0,
        ]);
    }

    public function test_user_can_initiate_fawry_topup_charge(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('wallet.topup'), [
            'amount' => 450.00,
            'credits' => 2500,
            'package_name' => 'باقة النمو (Growth)',
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals('success', $data['status']);
        $charge = $data['charge'];
        $this->assertNotNull($charge['reference_number']);
        $this->assertEquals(450.00, $charge['amount']);
        $this->assertEquals(2500, $charge['credits']);

        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $user->id,
            'reference_number' => $charge['reference_number'],
            'type' => 'bulk_credit',
            'status' => 'pending',
            'amount' => 450.00,
        ]);
    }

    public function test_fawry_webhook_credits_user_wallet_when_paid(): void
    {
        $user = User::factory()->create();
        $refNumber = 'WALLET-'.time().'-TEST';

        $tx = PaymentTransaction::create([
            'user_id' => $user->id,
            'reference_number' => $refNumber,
            'fawry_reference_number' => 'FAWRY-'.time(),
            'type' => 'bulk_credit',
            'amount' => 450.00,
            'currency' => 'EGP',
            'status' => 'pending',
            'payment_method' => 'fawry',
            'payment_data' => [
                'package_id' => 'growth',
                'credits' => 2500,
            ],
        ]);

        $payload = [
            'merchantRefNum' => $refNumber,
            'fawryRefNumber' => $tx->fawry_reference_number,
            'paymentAmount' => 450.00,
            'orderStatus' => 'PAID',
        ];

        $response = $this->postJson(route('webhooks.fawry'), $payload);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $tx->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('bulk_message_wallets', [
            'user_id' => $user->id,
            'balance' => 450.00,
            'credits' => 2500,
        ]);

        $this->assertDatabaseHas('bulk_message_transactions', [
            'user_id' => $user->id,
            'type' => 'topup',
            'amount' => 450.00,
            'credits' => 2500,
            'balance_after' => 450.00,
            'credits_after' => 2500,
        ]);
    }

    public function test_superadmin_can_manually_adjust_user_wallet_balance(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $targetUser = User::factory()->create();

        // 1. Add credit
        $response = $this->actingAs($admin)->post(route('superadmin.users.wallet-adjust', $targetUser->id), [
            'amount' => 200.00,
            'credits' => 1000,
            'type' => 'add',
            'reason' => 'Direct cash deposit test',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('bulk_message_wallets', [
            'user_id' => $targetUser->id,
            'balance' => 200.00,
            'credits' => 1000,
        ]);

        $this->assertDatabaseHas('bulk_message_transactions', [
            'user_id' => $targetUser->id,
            'type' => 'manual_adjustment',
            'amount' => 200.00,
            'credits' => 1000,
        ]);

        // 2. Deduct credit
        $response2 = $this->actingAs($admin)->post(route('superadmin.users.wallet-adjust', $targetUser->id), [
            'amount' => 50.00,
            'credits' => 250,
            'type' => 'deduct',
            'reason' => 'Adjustment refund',
        ]);

        $response2->assertRedirect();

        $this->assertDatabaseHas('bulk_message_wallets', [
            'user_id' => $targetUser->id,
            'balance' => 150.00,
            'credits' => 750,
        ]);
    }
}
