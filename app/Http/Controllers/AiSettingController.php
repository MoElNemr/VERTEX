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
    public function show(Request $request, Business $business): Response
    {
        $this->authorizeBusinessAccess($business);

        session(['current_business_id' => $business->id]);
        $request->merge(['tab' => 'ai']);

        return app(ChannelController::class)->index($request, $business);
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusinessAccess($business);

        session(['current_business_id' => $business->id]);

        $validated = $request->validate([
            'ai_enabled' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
            'ai_model' => ['nullable', 'string', 'in:gpt-4o-mini,gpt-4o,gpt-4-turbo,gemini-1.5-flash,claude-3-5-sonnet'],
            'model' => ['nullable', 'string'],
            'ai_personality' => ['nullable', 'string', 'max:1000'],
            'ai_instructions' => ['nullable', 'string', 'max:5000'],
            'system_prompt' => ['nullable', 'string', 'max:5000'],
            'working_hours_only' => ['nullable', 'boolean'],
            'work_start_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'work_end_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'auto_reply_platforms' => ['nullable', 'array'],
            'reply_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        // Synchronize naming variants for full compatibility
        $enabled = $request->has('ai_enabled') ? (bool) $request->input('ai_enabled') : (bool) $request->input('is_enabled', false);
        $model = $request->input('ai_model') ?: ($request->input('model') ?: 'gpt-4o-mini');
        $instructions = $request->input('ai_instructions') ?: ($request->input('system_prompt') ?: '');

        $dataToSave = [
            'ai_enabled' => $enabled,
            'is_enabled' => $enabled,
            'ai_model' => $model,
            'model' => $model,
            'ai_personality' => $request->input('ai_personality', ''),
            'ai_instructions' => $instructions,
            'system_prompt' => $instructions,
            'working_hours_only' => (bool) $request->input('working_hours_only', false),
            'work_start_hour' => (int) ($request->input('work_start_hour') ?? 9),
            'work_end_hour' => (int) ($request->input('work_end_hour') ?? 18),
            'auto_reply_platforms' => $request->input('auto_reply_platforms') ?? ['whatsapp', 'facebook', 'instagram', 'telegram'],
            'reply_delay_seconds' => (int) ($request->input('reply_delay_seconds') ?? 2),
        ];

        BusinessAiSetting::withoutGlobalScopes()->updateOrCreate(
            ['business_id' => $business->id],
            $dataToSave
        );

        return back()->with('success', 'تم حفظ إعدادات الذكاء الاصطناعي لمتجر ' . $business->name . ' بنجاح.');
    }

    protected function authorizeBusinessAccess(Business $business): void
    {
        $user = auth('web')->user() ?: auth('team')->user();
        if (!$user) {
            abort(401);
        }

        if (auth('web')->user() && (bool) auth('web')->user()->is_super_admin) {
            return;
        }

        $isOwner = (bool) auth('web')->user();
        $hasAccess = Business::where('id', $business->id)
            ->where(function ($q) use ($user, $isOwner) {
                if ($isOwner) {
                    $q->where('user_id', $user->id);
                } else {
                    $q->whereHas('teamMembers', function ($tmQ) use ($user) {
                        $tmQ->where('team_members.id', $user->id)
                            ->where('is_active', true);
                    });
                }
            })->exists();

        if (!$hasAccess) {
            abort(403, 'غير مصرح لك بالوصول لإعدادات هذا المتجر.');
        }
    }
}
