<?php

namespace Tests\Unit;

use App\Models\BulkMessageWallet;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagePricingTest extends TestCase
{
    use RefreshDatabase;
    public function test_message_pricing_defaults_and_custom_settings(): void
    {
        $walletService = new WalletService();

        // Defaults
        $this->assertEquals(0.20, $walletService->getBulkMessagePrice());
        $this->assertEquals(0.25, $walletService->getRetargetingMessagePrice());
        $this->assertEquals(1, $walletService->getBulkMessageCreditCost());
        $this->assertEquals(1, $walletService->getRetargetingMessageCreditCost());

        $pricing = $walletService->getMessagePricing();
        $this->assertArrayHasKey('bulk', $pricing);
        $this->assertArrayHasKey('retargeting', $pricing);

        // Custom SuperAdmin setting
        SystemSetting::set('bulk_message_price', 0.15, 'pricing');
        SystemSetting::set('retargeting_message_price', 0.35, 'pricing');
        SystemSetting::set('bulk_message_credit_cost', 1, 'pricing');
        SystemSetting::set('retargeting_message_credit_cost', 2, 'pricing');

        $this->assertEquals(0.15, $walletService->getBulkMessagePrice());
        $this->assertEquals(0.35, $walletService->getRetargetingMessagePrice());
        $this->assertEquals(1, $walletService->getBulkMessageCreditCost());
        $this->assertEquals(2, $walletService->getRetargetingMessageCreditCost());
    }
}
