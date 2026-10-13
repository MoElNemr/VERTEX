<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAiSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PerBusinessAiChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected User $merchant;
    protected Business $shoesStore;
    protected Business $clothesStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = User::factory()->create();

        // متجر أحذية
        $this->shoesStore = Business::create([
            'user_id' => $this->merchant->id,
            'name' => 'متجر الأحذية الذهبية',
        ]);

        // متجر ملابس
        $this->clothesStore = Business::create([
            'user_id' => $this->merchant->id,
            'name' => 'متجر ملابس الأناقة',
        ]);
    }

    public function test_channels_page_loads_with_ai_settings_for_each_business(): void
    {
        // Channels page for Shoes Store
        $res = $this->actingAs($this->merchant)
            ->get(route('channels.index', $this->shoesStore->id));

        $res->assertOk();
        $this->assertEquals($this->shoesStore->id, $res->viewData('page')['props']['business']['id']);
        $this->assertEquals($this->shoesStore->id, $res->viewData('page')['props']['aiSetting']['business_id']);

        // Channels page for Clothes Store
        $resClothes = $this->actingAs($this->merchant)
            ->get(route('channels.index', $this->clothesStore->id));

        $resClothes->assertOk();
        $this->assertEquals($this->clothesStore->id, $resClothes->viewData('page')['props']['business']['id']);
        $this->assertEquals($this->clothesStore->id, $resClothes->viewData('page')['props']['aiSetting']['business_id']);
    }

    public function test_each_store_maintains_independent_ai_prompts_and_settings(): void
    {
        // 1. Configure Shoes Store AI
        $shoesPrompt = "قواعد متجر الأحذية: المقاسات أوروبية من 37 إلى 46، شحن 48 ساعة مع إمكانية المعاينة قبل الاستلام.";
        $this->actingAs($this->merchant)
            ->post(route('ai-settings.update', $this->shoesStore->id), [
                'ai_enabled' => true,
                'ai_model' => 'gpt-4o-mini',
                'ai_personality' => 'مستشار مبيعات خبير في الأحذية والمقاسات',
                'ai_instructions' => $shoesPrompt,
                'working_hours_only' => false,
            ])
            ->assertRedirect();

        // 2. Configure Clothes Store AI
        $clothesPrompt = "قواعد متجر الملابس: كولكشن الصيف مقاسات S إلى 3XL، قطن 100%، الاسترجاع مع وجود التيكت كارت.";
        $this->actingAs($this->merchant)
            ->post(route('ai-settings.update', $this->clothesStore->id), [
                'ai_enabled' => false,
                'ai_model' => 'gpt-4o',
                'ai_personality' => 'منسق أزياء راقي يساعد في تنسيق الأطقم',
                'ai_instructions' => $clothesPrompt,
                'working_hours_only' => true,
                'work_start_hour' => 10,
                'work_end_hour' => 19,
            ])
            ->assertRedirect();

        // 3. Assert Shoes Store in DB has exact shoes prompt and is enabled
        $shoesSetting = BusinessAiSetting::withoutGlobalScopes()->where('business_id', $this->shoesStore->id)->first();
        $this->assertNotNull($shoesSetting);
        $this->assertTrue((bool) $shoesSetting->ai_enabled);
        $this->assertEquals('gpt-4o-mini', $shoesSetting->ai_model);
        $this->assertEquals($shoesPrompt, $shoesSetting->ai_instructions);
        $this->assertFalse((bool) $shoesSetting->working_hours_only);

        // 4. Assert Clothes Store in DB has exact clothes prompt and is disabled
        $clothesSetting = BusinessAiSetting::withoutGlobalScopes()->where('business_id', $this->clothesStore->id)->first();
        $this->assertNotNull($clothesSetting);
        $this->assertFalse((bool) $clothesSetting->ai_enabled);
        $this->assertEquals('gpt-4o', $clothesSetting->ai_model);
        $this->assertEquals($clothesPrompt, $clothesSetting->ai_instructions);
        $this->assertTrue((bool) $clothesSetting->working_hours_only);
        $this->assertEquals(10, $clothesSetting->work_start_hour);
        $this->assertEquals(19, $clothesSetting->work_end_hour);

        // 5. Updating Clothes Store again does NOT affect Shoes Store
        $this->actingAs($this->merchant)
            ->post(route('ai-settings.update', $this->clothesStore->id), [
                'ai_enabled' => true,
                'ai_instructions' => 'تحديث جديد لمتجر الملابس فقط',
            ])
            ->assertRedirect();

        $this->assertEquals($shoesPrompt, $shoesSetting->fresh()->ai_instructions);
        $this->assertEquals('تحديث جديد لمتجر الملابس فقط', $clothesSetting->fresh()->ai_instructions);
    }

    public function test_ai_settings_show_route_renders_channels_page_with_ai_tab(): void
    {
        $res = $this->actingAs($this->merchant)
            ->get(route('ai-settings.show', $this->shoesStore->id));

        $res->assertOk();
        $this->assertEquals('Businesses/Channels', $res->viewData('page')['component']);
        $this->assertEquals('ai', $res->viewData('page')['props']['initialTab']);
    }
}
