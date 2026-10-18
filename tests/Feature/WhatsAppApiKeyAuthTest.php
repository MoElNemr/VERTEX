<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\Channels\WhatsAppChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppApiKeyAuthTest extends TestCase
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
            'name' => 'Secure Store',
        ]);
    }

    public function test_outbound_requests_attach_api_key_header(): void
    {
        Config::set('services.whatsapp.service_url', 'https://wa.js.vertex.78sworks.io');
        Config::set('services.whatsapp.api_key', 'test_secret_token_abc123');

        Http::fake([
            'https://wa.js.vertex.78sworks.io/api/sessions/start' => Http::response([
                'status' => 'qr_ready',
                'session_id' => 'session_' . $this->business->id,
                'qr_code' => 'mock_qr_string',
            ], 200),
        ]);

        $service = new WhatsAppChannelService();
        $result = $service->requestQrCode($this->business);

        $this->assertEquals('qr_ready', $result['status']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-key', 'test_secret_token_abc123')
                && $request->hasHeader('Authorization', 'Bearer test_secret_token_abc123');
        });
    }

    public function test_webhook_rejects_missing_api_key_when_configured(): void
    {
        Config::set('services.whatsapp.api_key', 'test_secret_token_abc123');

        $response = $this->postJson(route('webhooks.whatsapp', $this->business->id), [
            'event' => 'connection.update',
            'data' => [
                'session_id' => 'session_' . $this->business->id,
                'status' => 'connected',
            ],
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'unauthorized',
        ]);
    }

    public function test_webhook_accepts_valid_api_key(): void
    {
        Config::set('services.whatsapp.api_key', 'test_secret_token_abc123');

        $response = $this->postJson(
            route('webhooks.whatsapp', $this->business->id),
            [
                'event' => 'connection.update',
                'data' => [
                    'session_id' => 'session_' . $this->business->id,
                    'status' => 'connected',
                    'phone_number' => '201012345678',
                ],
            ],
            ['x-api-key' => 'test_secret_token_abc123']
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('whatsapp_accounts', [
            'business_id' => $this->business->id,
            'status' => 'connected',
            'phone_number' => '201012345678',
        ]);
    }

    public function test_webhook_accepts_webhook_secret_header(): void
    {
        Config::set('services.whatsapp.api_key', 'test_secret_token_abc123');

        $response = $this->postJson(
            route('webhooks.whatsapp', $this->business->id),
            [
                'event' => 'connection.update',
                'data' => [
                    'session_id' => 'session_' . $this->business->id,
                    'status' => 'connected',
                    'phone_number' => '201099998888',
                ],
            ],
            ['x-webhook-secret' => 'test_secret_token_abc123']
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('whatsapp_accounts', [
            'business_id' => $this->business->id,
            'phone_number' => '201099998888',
        ]);
    }
}
