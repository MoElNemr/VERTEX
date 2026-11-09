<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MarketingAndCampaignsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'name' => 'متجر الإلكترونيات التجريبي',
        ]);

        session(['current_business_id' => $this->business->id]);

        WhatsappAccount::create([
            'business_id' => $this->business->id,
            'session_id' => 'session_'.$this->business->id,
            'status' => 'connected',
            'phone_number' => '+201000000000',
        ]);
    }

    public function test_dashboard_renders_with_bento_marketing_and_roi_metrics(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Dashboard')
            ->has('marketing.roi_multiplier')
            ->has('marketing.platform_distribution')
            ->has('marketing.time_series')
            ->has('marketing.delivery_rate')
        );
    }

    public function test_retargeting_index_renders_and_calculates_platform_audience(): void
    {
        // Create sample contacts across the 4 platforms
        Contact::create([
            'business_id' => $this->business->id,
            'platform' => 'whatsapp',
            'platform_sender_id' => '201012345678',
            'name' => 'أحمد علي',
            'phone' => '201012345678',
        ]);

        Contact::create([
            'business_id' => $this->business->id,
            'platform' => 'facebook',
            'platform_sender_id' => 'fb_123456',
            'name' => 'سارة محمد',
        ]);

        Contact::create([
            'business_id' => $this->business->id,
            'platform' => 'instagram',
            'platform_sender_id' => 'ig_98765',
            'name' => 'محمود حسن',
        ]);

        Contact::create([
            'business_id' => $this->business->id,
            'platform' => 'telegram',
            'platform_sender_id' => 'tg_554433',
            'name' => 'ياسمين خالد',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->get(route('retargeting.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Retargeting/Index')
            ->where('audience.total', 4)
            ->where('audience.by_platform.whatsapp', 1)
            ->where('audience.by_platform.facebook', 1)
            ->where('audience.by_platform.instagram', 1)
            ->where('audience.by_platform.telegram', 1)
        );
    }

    public function test_can_create_and_process_retargeting_campaign_with_anti_ban_intervals(): void
    {
        Contact::create([
            'business_id' => $this->business->id,
            'platform' => 'whatsapp',
            'platform_sender_id' => '201100000001',
            'name' => 'عميل تجريبي 1',
            'phone' => '201100000001',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->post(route('retargeting.store'), [
                'name' => 'حملة عطلة الأسبوع',
                'platforms' => ['whatsapp'],
                'message' => 'أهلاً {name}، خصم 20% بانتظارك!',
                'interval_seconds' => 12,
                'jitter_seconds' => 3,
                'auto_start' => false,
            ]);

        $response->assertRedirect(route('retargeting.index'));

        $campaign = Campaign::where('business_id', $this->business->id)->first();
        $this->assertNotNull($campaign);
        $this->assertEquals('حملة عطلة الأسبوع', $campaign->name);
        $this->assertEquals(12, $campaign->interval_seconds);
        $this->assertEquals(3, $campaign->jitter_seconds);
        $this->assertEquals(1, $campaign->total_recipients);

        // Process step
        $stepResponse = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->postJson(route('retargeting.step', $campaign->id));

        $stepResponse->assertOk();
        $this->assertEquals('completed', $campaign->fresh()->status);
        $this->assertEquals(1, $campaign->fresh()->sent_count);
    }

    public function test_can_preview_csv_and_download_template_in_whatsapp_sender(): void
    {
        // 1. Template download
        $templateResponse = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->get(route('whatsapp-sender.template'));

        $templateResponse->assertOk();
        $templateResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 2. CSV Upload & Preview
        $csvContent = "name,phone\nكريم عادل,01011223344\nمنى أحمد,0501234567\nرقم خاطئ,123\n";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $previewResponse = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->post(route('whatsapp-sender.preview-csv'), [
                'file' => $file,
            ]);

        $previewResponse->assertOk();
        $previewResponse->assertJson([
            'status' => 'success',
            'total_rows' => 3,
            'valid_count' => 2,
            'invalid_count' => 1,
        ]);
    }

    public function test_can_launch_bulk_whatsapp_campaign_and_save_to_contacts(): void
    {
        $payload = [
            'name' => 'إرسال لعملاء VIP من CSV',
            'message' => 'عزيزي {name}، كود خصمك هو VIP2026',
            'recipients' => [
                ['name' => 'فادي جورج', 'phone' => '01055554444'],
                ['name' => 'زياد طارق', 'phone' => '01122223333'],
            ],
            'interval_seconds' => 8,
            'jitter_seconds' => 2,
            'save_to_contacts' => true,
            'auto_start' => true,
        ];

        $walletService = app(WalletService::class);
        $walletService->creditWallet($this->user, 50.00, 20, null, 'Test top-up for bulk send');

        $response = $this->actingAs($this->user)
            ->withSession(['current_business_id' => $this->business->id])
            ->postJson(route('whatsapp-sender.send'), $payload);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $campaign = Campaign::where('type', 'whatsapp_bulk')->first();
        $this->assertNotNull($campaign);
        $this->assertEquals(2, $campaign->total_recipients);
        $this->assertEquals('processing', $campaign->status);

        // Verify contacts were auto-saved
        $this->assertDatabaseHas('contacts', [
            'business_id' => $this->business->id,
            'name' => 'فادي جورج',
            'platform' => 'whatsapp',
        ]);
    }
}
