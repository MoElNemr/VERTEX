<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAiSetting;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CentralizedGroqAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_save_groq_settings(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $response = $this->actingAs($superAdmin)->post(route('superadmin.settings.groq'), [
            'api_key' => 'gsk_superadmin_central_secret_key_12345',
            'model' => 'llama-3.3-70b-versatile',
            'enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertEquals('gsk_superadmin_central_secret_key_12345', SystemSetting::get('groq_api_key'));
        $this->assertEquals('llama-3.3-70b-versatile', SystemSetting::get('groq_model'));
        $this->assertTrue((bool) SystemSetting::get('groq_enabled'));
    }

    public function test_ai_service_uses_central_superadmin_key_without_requiring_merchant_key(): void
    {
        SystemSetting::set('groq_api_key', 'gsk_central_system_key_abc', 'groq');
        SystemSetting::set('groq_model', 'llama-3.3-70b-versatile', 'groq');
        SystemSetting::set('groq_enabled', true, 'groq');

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'مرحباً، تم الرد عبر المفتاح المركزي بنجاح!']],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $business = Business::create(['name' => 'متجر الأحذية', 'user_id' => $user->id]);
        $setting = BusinessAiSetting::create([
            'business_id' => $business->id,
            'is_enabled' => true,
            'ai_enabled' => true,
            'ai_personality' => 'ودود',
            'api_key' => null, // Merchant has NO API key!
        ]);

        $aiService = app(AiService::class);
        $reply = $aiService->generateCommentReply('بكام الحذاء؟', 'أحمد', 'instagram', $setting);

        $this->assertEquals('مرحباً، تم الرد عبر المفتاح المركزي بنجاح!', $reply);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer gsk_central_system_key_abc');
        });
    }

    public function test_ai_service_blocks_requests_when_globally_disabled(): void
    {
        SystemSetting::set('groq_api_key', 'gsk_central_key_xyz', 'groq');
        SystemSetting::set('groq_enabled', false, 'groq');

        Http::fake();

        $aiService = app(AiService::class);
        $result = $aiService->callGroq([['role' => 'user', 'content' => 'مرحبا']]);

        $this->assertNull($result);
        Http::assertNothingSent();
    }
}
