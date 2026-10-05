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

        return Inertia::render('Businesses/Channels', [
            'business' => $business->only(['id', 'name', 'logo']),
            'whatsapp' => $business->whatsappAccount,
            'facebook' => $business->facebookAccount,
            'instagram' => $business->instagramAccount,
            'telegram' => $business->telegramAccount,
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
            'whatsapp' => $business->whatsappAccount()?->delete(),
            default => null,
        };

        return back()->with('success', 'تم فصل القناة بنجاح.');
    }

    /**
     * Helper to verify user permissions.
     */
    protected function authorizeBusiness(Request $request, Business $business): void
    {
        $user = $request->user('web') ?: $request->user('team');

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
