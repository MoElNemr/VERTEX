<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Plan;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CampaignBalanceGateTest extends TestCase
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
    }

    public function test_csv_preview_returns_wallet_affordability_stats(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['user_id' => $user->id, 'name' => 'Test Store']);

        // Set wallet balance to 10 credits
        $walletService = app(WalletService::class);
        $walletService->creditWallet($user, 20.00, 10, null, 'Initial test top-up');

        // CSV with 3 contacts (can afford)
        $csvContent = "name,phone\nAli,+201012345678\nSara,+201098765432\nOmar,+201123456789\n";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->postJson(route('whatsapp-sender.preview-csv'), [
                'file' => $file,
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(3, $data['valid_count']);
        $this->assertTrue($data['wallet']['can_afford']);
        $this->assertEquals(10, $data['wallet']['current_credits']);
        $this->assertEquals(3, $data['wallet']['required_credits']);
        $this->assertEquals(0, $data['wallet']['missing_credits']);
    }

    public function test_campaign_send_is_blocked_with_402_if_wallet_cannot_afford_recipients(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['user_id' => $user->id, 'name' => 'Test Store']);

        // User has only 2 credits
        $walletService = app(WalletService::class);
        $walletService->creditWallet($user, 5.00, 2, null, 'Low balance');

        // Attempt to send to 5 recipients
        $recipients = [
            ['name' => 'User 1', 'phone' => '+201000000001'],
            ['name' => 'User 2', 'phone' => '+201000000002'],
            ['name' => 'User 3', 'phone' => '+201000000003'],
            ['name' => 'User 4', 'phone' => '+201000000004'],
            ['name' => 'User 5', 'phone' => '+201000000005'],
        ];

        $response = $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->postJson(route('whatsapp-sender.send'), [
                'name' => 'Test Bulk Promo',
                'message' => 'Hello {name}!',
                'recipients' => $recipients,
            ]);

        $response->assertStatus(402);
        $data = $response->json();

        $this->assertStringContainsString('رصيد المحفظة غير كافٍ', $data['error']);
        $this->assertEquals(2, $data['wallet']['current_credits']);
        $this->assertEquals(5, $data['wallet']['required_credits']);
        $this->assertEquals(3, $data['wallet']['missing_credits']);

        // Ensure no campaign was created
        $this->assertDatabaseMissing('campaigns', ['name' => 'Test Bulk Promo']);
    }

    public function test_campaign_send_succeeds_and_deducts_credits_per_message(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['user_id' => $user->id, 'name' => 'Test Store']);

        // Connect WhatsApp account
        $business->whatsappAccount()->create([
            'session_id' => 'session_'.$business->id,
            'status' => 'connected',
        ]);

        // User has 10 credits
        $walletService = app(WalletService::class);
        $walletService->creditWallet($user, 20.00, 10, null, 'Sufficient balance');

        $recipients = [
            ['name' => 'Customer A', 'phone' => '+201011111111'],
            ['name' => 'Customer B', 'phone' => '+201022222222'],
        ];

        $response = $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->postJson(route('whatsapp-sender.send'), [
                'name' => 'Affordable Promo',
                'message' => 'Hello {name}!',
                'recipients' => $recipients,
                'auto_start' => false,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('campaigns', ['name' => 'Affordable Promo']);

        $campaign = Campaign::where('name', 'Affordable Promo')->first();
        $this->assertEquals(2, $campaign->total_recipients);

        // Process step 1 -> should deduct 1 credit
        $stepResponse = $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->postJson(route('whatsapp-sender.step', $campaign->id));

        $stepResponse->assertOk();

        $wallet = $user->bulkWallet()->first();
        $this->assertEquals(9, $wallet->credits);

        $this->assertDatabaseHas('bulk_message_transactions', [
            'user_id' => $user->id,
            'business_id' => $business->id,
            'type' => 'deduct',
            'credits' => -1,
            'credits_after' => 9,
        ]);
    }

    public function test_retargeting_campaign_does_not_deduct_credits_from_bulk_wallet(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['user_id' => $user->id, 'name' => 'Test Store']);

        // Create 2 existing contacts in business
        $contact1 = Contact::create([
            'business_id' => $business->id,
            'platform' => 'whatsapp',
            'platform_sender_id' => '+201099999991',
            'name' => 'Existing 1',
        ]);

        $contact2 = Contact::create([
            'business_id' => $business->id,
            'platform' => 'whatsapp',
            'platform_sender_id' => '+201099999992',
            'name' => 'Existing 2',
        ]);

        // User starts with 5 credits
        $walletService = app(WalletService::class);
        $walletService->creditWallet($user, 10.00, 5, null, 'Test balance');

        $campaignService = app(CampaignService::class);
        $campaign = $campaignService->createRetargetingCampaign($business, [
            'name' => 'Existing Customers Promo',
            'platforms' => ['whatsapp'],
            'message' => 'Hello {name}!',
        ]);

        $this->assertEquals(2, $campaign->total_recipients);

        // Process step
        $campaignService->processNextRecipient($campaign);

        // Wallet credits must remain untouched at 5 because retargeting is included in monthly plan
        $wallet = $user->bulkWallet()->first();
        $this->assertEquals(5, $wallet->credits);
    }

    public function test_failed_message_dispatch_does_not_deduct_wallet_credits(): void
    {
        $user = User::factory()->create();
        $business = Business::create(['user_id' => $user->id, 'name' => 'Disconnected Store']);

        // User has 10 credits
        $walletService = app(WalletService::class);
        $walletService->creditWallet($user, 20.00, 10, null, 'Sufficient balance');

        // Business has NO connected channel -> dispatch will fail
        $recipients = [
            ['name' => 'Customer A', 'phone' => '+201011111111'],
        ];

        $response = $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->postJson(route('whatsapp-sender.send'), [
                'name' => 'Failed Campaign',
                'message' => 'Hello {name}!',
                'recipients' => $recipients,
                'auto_start' => false,
            ]);

        $response->assertOk();
        $campaign = Campaign::where('name', 'Failed Campaign')->first();

        // Process step -> message dispatch fails
        $stepResponse = $this->actingAs($user)
            ->withSession(['current_business_id' => $business->id])
            ->postJson(route('whatsapp-sender.step', $campaign->id));

        $stepResponse->assertOk();

        // Credits must still be 10!
        $wallet = $user->bulkWallet()->first();
        $this->assertEquals(10, $wallet->credits);

        $this->assertDatabaseMissing('bulk_message_transactions', [
            'user_id' => $user->id,
            'type' => 'deduct',
        ]);
    }
}
