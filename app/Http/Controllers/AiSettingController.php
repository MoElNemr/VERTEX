<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessAiSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

/**
 * وحدة التحكم بإعدادات المساعد الذكي للمتجر
 */
class AiSettingController extends Controller
{
    public function show(Business $business): Response
    {
        $this->authorizeBusinessAccess($business);

        $setting = BusinessAiSetting::firstOrCreate(
            ['business_id' => $business->id],
            [
                'ai_enabled' => false,
                'ai_model' => 'gpt-4o-mini',
                'ai_personality' => '',
                'ai_instructions' => '',
                'working_hours_only' => false,
                'work_start_hour' => 9,
                'work_end_hour' => 18,
            ]
        );

        return Inertia::render('AiSettings/Index', [
            'business' => $business->only(['id', 'name']),
            'setting' => $setting,
        ]);
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusinessAccess($business);

        $validated = $request->validate([
            'ai_enabled' => ['boolean'],
            'ai_model' => ['string', 'in:gpt-4o-mini,gpt-4o,gpt-4-turbo'],
            'ai_personality' => ['nullable', 'string', 'max:1000'],
            'ai_instructions' => ['nullable', 'string', 'max:3000'],
            'working_hours_only' => ['boolean'],
            'work_start_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'work_end_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
        ]);

        BusinessAiSetting::updateOrCreate(
            ['business_id' => $business->id],
            $validated
        );

        return back()->with('success', 'تم حفظ إعدادات المساعد الذكي.');
    }

    protected function authorizeBusinessAccess(Business $business): void
    {
        $user = auth('web')->user();
        if (!$user || $business->user_id !== $user->id) {
            abort(403, 'غير مصرح لك بالوصول لإعدادات هذا المتجر.');
        }
    }
}
