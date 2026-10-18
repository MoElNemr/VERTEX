<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ChannelController extends Controller
{
    /**
     * Show channels configuration page for a business.
     */
    public function index(Request $request, Business $business): Response
    {
        $this->authorizeBusiness($request, $business);

        session(['current_business_id' => $business->id]);

        $aiSetting = \App\Models\BusinessAiSetting::withoutGlobalScopes()->firstOrCreate(
            ['business_id' => $business->id],
            [
                'ai_enabled' => false,
                'is_enabled' => false,
                'provider' => 'groq',
                'ai_model' => 'llama-3.3-70b-versatile',
                'model' => 'llama-3.3-70b-versatile',
                'ai_personality' => 'أنت ممثل مبيعات وخدمة عملاء ودود واحترافي لمتجر ' . $business->name . '، تتحدث بلباقة وترحب بالعميل دائمًا.',
                'ai_instructions' => '',
                'system_prompt' => '',
                'working_hours_only' => false,
                'work_start_hour' => 9,
                'work_end_hour' => 18,
                'auto_reply_platforms' => ['whatsapp', 'facebook', 'instagram', 'telegram'],
                'reply_delay_seconds' => 2,
            ]
        );

        $ownerId = auth('web')->user() ? auth('web')->id() : $business->user_id;
        $sharedApiKey = null;
        if ($ownerId) {
            $otherSetting = \App\Models\BusinessAiSetting::withoutGlobalScopes()
                ->whereIn('business_id', Business::where('user_id', $ownerId)->where('id', '!=', $business->id)->pluck('id'))
                ->whereNotNull('api_key')
                ->where('api_key', '!=', '')
                ->first();
            if ($otherSetting) {
                $sharedApiKey = $otherSetting->api_key;
            }
        }

        return Inertia::render('Businesses/Channels', [
            'business' => $business->only(['id', 'name', 'logo']),
            'whatsapp' => $business->whatsappAccount,
            'facebook' => $business->facebookAccount,
            'instagram' => $business->instagramAccount,
            'telegram' => $business->telegramAccount,
            'aiSetting' => $aiSetting,
            'sharedApiKey' => $sharedApiKey,
            'initialTab' => $request->query('tab', 'channels'),
        ]);
    }

    /**
     * Save / Connect Telegram Bot.
     */
    public function connectTelegram(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $validated = $request->validate([
            'bot_token' => ['required', 'string'],
        ]);

        $token = trim($validated['bot_token']);

        // فحص صحة التوكن وجلب معلومات البوت من تليجرام
        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");

            if (!$response->successful() || !$response->json('ok')) {
                return back()->withErrors(['bot_token' => 'توكن البوت غير صحيح أو غير متاح في تيليجرام.']);
            }

            $botData = $response->json('result');
            $secret = Str::random(40);

            // تفعيل الـ Webhook
            $webhookUrl = route('webhooks.telegram', ['business' => $business->id]);
            $setWebhook = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
                'secret_token' => $secret,
            ]);

            $business->telegramAccount()->updateOrCreate(
                ['business_id' => $business->id],
                [
                    'bot_username' => $botData['username'] ?? null,
                    'bot_token' => $token,
                    'webhook_secret' => $secret,
                    'status' => 'connected',
                ]
            );

            return back()->with('success', 'تم ربط بوت تيليجرام بنجاح!');
        } catch (\Exception $e) {
            return back()->withErrors(['bot_token' => 'تعذر الاتصال بخوادم تيليجرام: ' . $e->getMessage()]);
        }
    }

    /**
     * Save / Connect Facebook Page.
     */
    public function connectFacebook(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $validated = $request->validate([
            'page_id' => ['required', 'string'],
            'page_access_token' => ['required', 'string'],
        ]);

        $pageId = trim($validated['page_id']);
        $token = trim($validated['page_access_token']);

        // التحقق من صحة التوكن وجلب اسم الصفحة عبر Meta Graph API
        try {
            $response = Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                'fields' => 'name,id',
                'access_token' => $token,
            ]);

            if (!$response->successful()) {
                $errorMsg = $response->json('error.message') ?? 'معرف الصفحة أو التوكن غير صحيح.';
                return back()->withErrors(['page_access_token' => $errorMsg]);
            }

            $pageData = $response->json();

            // الاشتراك في الـ Webhooks للصفحة تلقائياً
            Http::timeout(10)->post("https://graph.facebook.com/v21.0/{$pageId}/subscribed_apps", [
                'subscribed_fields' => 'messages,messaging_postbacks,message_reads,feed',
                'access_token' => $token,
            ]);

            $business->facebookAccount()->updateOrCreate(
                ['business_id' => $business->id],
                [
                    'page_id' => $pageData['id'],
                    'page_name' => $pageData['name'] ?? 'Facebook Page',
                    'page_access_token' => $token,
                    'status' => 'connected',
                ]
            );

            return back()->with('success', 'تم ربط صفحة فيسبوك بنجاح!');
        } catch (\Exception $e) {
            return back()->withErrors(['page_access_token' => 'خطأ أثناء الاتصال بميتا: ' . $e->getMessage()]);
        }
    }

    /**
     * Save / Connect Instagram Business Account.
     */
    public function connectInstagram(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $validated = $request->validate([
            'instagram_business_id' => ['required', 'string'],
            'access_token' => ['required', 'string'],
        ]);

        $igId = trim($validated['instagram_business_id']);
        $token = trim($validated['access_token']);

        try {
            $response = Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$igId}", [
                'fields' => 'username,name,id',
                'access_token' => $token,
            ]);

            if (!$response->successful()) {
                $errorMsg = $response->json('error.message') ?? 'معرف إنستجرام أو التوكن غير صحيح.';
                return back()->withErrors(['access_token' => $errorMsg]);
            }

            $igData = $response->json();

            $business->instagramAccount()->updateOrCreate(
                ['business_id' => $business->id],
                [
                    'instagram_business_id' => $igData['id'],
                    'username' => $igData['username'] ?? null,
                    'access_token' => $token,
                    'status' => 'connected',
                ]
            );

            return back()->with('success', 'تم ربط حساب إنستجرام بنجاح!');
        } catch (\Exception $e) {
            return back()->withErrors(['access_token' => 'خطأ أثناء الاتصال بإنستجرام: ' . $e->getMessage()]);
        }
    }

    /**
     * Disconnect a channel.
     */
    public function disconnect(Request $request, Business $business, string $channel): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        match ($channel) {
            'telegram' => $business->telegramAccount()?->delete(),
            'facebook' => $business->facebookAccount()?->delete(),
            'instagram' => $business->instagramAccount()?->delete(),
            'whatsapp' => app(\App\Services\Channels\WhatsAppChannelService::class)->disconnect($business),
            default => null,
        };

        return back()->with('success', 'تم فصل القناة بنجاح.');
    }

    /**
     * Request 8-digit Pairing Code for WhatsApp connection.
     */
    public function requestWhatsAppPairingCode(Request $request, Business $business): \Illuminate\Http\JsonResponse
    {
        $this->authorizeBusiness($request, $business);

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'min:8', 'max:20'],
        ]);

        $result = app(\App\Services\Channels\WhatsAppChannelService::class)->requestPairingCode($business, $validated['phone_number']);

        return response()->json($result);
    }

    /**
     * Check current WhatsApp connection status.
     */
    public function checkWhatsAppStatus(Request $request, Business $business): \Illuminate\Http\JsonResponse
    {
        $this->authorizeBusiness($request, $business);

        $account = $business->whatsappAccount;

        return response()->json([
            'status' => $account?->status ?? 'disconnected',
            'phone_number' => $account?->phone_number,
            'connected' => ($account?->status === 'connected'),
        ]);
    }

    /**
     * Update Comment-to-DM automation settings for Facebook or Instagram.
     */
    public function updateCommentSettings(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $validated = $request->validate([
            'platform' => ['required', 'in:facebook,instagram'],
            'comment_reply_enabled' => ['required', 'boolean'],
            'comment_reply_text' => ['nullable', 'string', 'max:1000'],
            'dm_message_text' => ['nullable', 'string', 'max:2000'],
            'comment_keywords' => ['nullable'],
        ]);

        $keywords = null;
        if (!empty($validated['comment_keywords'])) {
            if (is_string($validated['comment_keywords'])) {
                $keywords = array_values(array_filter(array_map('trim', explode(',', $validated['comment_keywords']))));
            } elseif (is_array($validated['comment_keywords'])) {
                $keywords = array_values(array_filter(array_map('trim', $validated['comment_keywords'])));
            }
        }

        if ($validated['platform'] === 'facebook') {
            $account = $business->facebookAccount;
            if (!$account) {
                return back()->with('error', 'يرجى ربط صفحة فيسبوك أولاً قبل ضبط إعدادات الرد على التعليقات.');
            }

            $account->update([
                'comment_reply_enabled' => $validated['comment_reply_enabled'],
                'comment_reply_text' => $validated['comment_reply_text'],
                'dm_message_text' => $validated['dm_message_text'],
                'comment_keywords' => $keywords,
            ]);

            return back()->with('success', 'تم حفظ إعدادات الرد التلقائي على تعليقات فيسبوك بنجاح!');
        }

        if ($validated['platform'] === 'instagram') {
            $account = $business->instagramAccount;
            if (!$account) {
                return back()->with('error', 'يرجى ربط حساب إنستجرام أولاً قبل ضبط إعدادات الرد على التعليقات.');
            }

            $account->update([
                'comment_reply_enabled' => $validated['comment_reply_enabled'],
                'comment_reply_text' => $validated['comment_reply_text'],
                'dm_message_text' => $validated['dm_message_text'],
                'comment_keywords' => $keywords,
            ]);

            return back()->with('success', 'تم حفظ إعدادات الرد التلقائي على تعليقات إنستجرام بنجاح!');
        }

        return back();
    }

    /**
     * Helper to verify user permissions.
     */
    protected function authorizeBusiness(Request $request, Business $business): void
    {
        $user = $request->user('web') ?: $request->user('team');

        if ($request->user('web') && (bool) $request->user('web')->is_super_admin) {
            return;
        }

        $hasAccess = Business::where('id', $business->id)
            ->where(function ($q) use ($user, $request) {
                if ($request->user('web')) {
                    $q->where('user_id', $user->id);
                } else {
                    $q->whereHas('teamMembers', function ($tmQ) use ($user) {
                        $tmQ->where('email', $user->email);
                    });
                }
            })->exists();

        if (!$hasAccess) {
            abort(403, 'غير مصرح لك بالوصول لهذا البيزنس.');
        }
    }
}
