<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\TiktokAccount;
use App\Services\TikTokOAuthService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokOAuthController extends Controller
{
    public function __construct(
        protected TikTokOAuthService $tiktokService
    ) {}

    /**
     * Redirect merchant to TikTok OAuth v2 authorization page.
     */
    public function redirectToTikTok(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $state = Str::random(40);
        session([
            'tiktok_oauth_state' => $state,
            'tiktok_oauth_business_id' => $business->id,
        ]);

        return redirect()->away($this->tiktokService->getAuthorizationUrl($state));
    }

    /**
     * Handle incoming OAuth callback from TikTok.
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $businessId = session('tiktok_oauth_business_id') ?? session('current_business_id');
        $business = Business::find($businessId);

        if (! $business) {
            return redirect()->route('businesses.index')
                ->with('error', 'تعذر تحديد المتجر المطلوب لربط حساب تيك توك.');
        }

        if ($request->has('error')) {
            $errorDesc = $request->input('error_description') ?? $request->input('error');
            Log::warning('TikTok OAuth authorization failed or cancelled', [
                'error' => $request->all(),
                'business_id' => $businessId,
            ]);

            return redirect()->route('channels.index', ['business' => $businessId])
                ->with('error', 'تم إلغاء أو رفض عملية الربط عبر تيك توك: '.$errorDesc);
        }

        $sessionState = session()->pull('tiktok_oauth_state');
        $returnedState = (string) $request->input('state');

        if (empty($sessionState) || empty($returnedState) || ! hash_equals($sessionState, $returnedState)) {
            Log::warning('TikTok OAuth state mismatch', [
                'session_state' => $sessionState,
                'returned_state' => $returnedState,
            ]);
            abort(403, 'فشلت عملية التحقق الأمني من الـ state. يرجى إعادة المحاولة.');
        }

        $code = (string) $request->input('code');
        if (empty($code)) {
            return redirect()->route('channels.index', ['business' => $businessId])
                ->with('error', 'لم يتم استلام رمز المصادقة من تيك توك.');
        }

        try {
            $tokenData = $this->tiktokService->exchangeCodeForToken($code);
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $openId = (string) ($tokenData['open_id'] ?? '');
            $expiresIn = (int) ($tokenData['expires_in'] ?? 86400);
            $refreshExpiresIn = (int) ($tokenData['refresh_expires_in'] ?? 31536000);

            // Fetch profile info
            $userInfo = $this->tiktokService->getUserInfo($accessToken);
            $openId = $openId ?: (string) ($userInfo['open_id'] ?? '');
            $unionId = (string) ($tokenData['union_id'] ?? $userInfo['union_id'] ?? null);
            $displayName = $userInfo['display_name'] ?? null;
            $username = $userInfo['username'] ?? null;
            $avatarUrl = $userInfo['avatar_url'] ?? null;

            if (empty($openId)) {
                throw new Exception('لم نتمكن من الحصول على معرف حساب تيك توك (OpenID).');
            }

            // Prevent collision with another business
            $existing = TiktokAccount::withoutGlobalScopes()
                ->where('open_id', $openId)
                ->where('business_id', '!=', $business->id)
                ->first();

            if ($existing) {
                return redirect()->route('channels.index', ['business' => $businessId])
                    ->with('error', 'حساب تيك توك هذا مربوط بالفعل بمتجر آخر في المنصة. يرجى فصله من المتجر الآخر أولاً.');
            }

            $business->tiktokAccount()->updateOrCreate(
                ['business_id' => $business->id],
                [
                    'open_id' => $openId,
                    'union_id' => $unionId,
                    'username' => $username,
                    'display_name' => $displayName,
                    'avatar_url' => $avatarUrl,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
                    'refresh_expires_at' => Carbon::now()->addSeconds($refreshExpiresIn),
                    'granted_scopes' => $this->tiktokService->getScopes(),
                    'status' => 'connected',
                ]
            );

            session()->forget(['tiktok_oauth_business_id']);

            return redirect()->route('channels.index', ['business' => $businessId])
                ->with('success', 'تم ربط حساب تيك توك للأعمال بنجاح.');
        } catch (Exception $e) {
            Log::error('TikTok OAuth callback exception', [
                'error' => $e->getMessage(),
                'business_id' => $businessId,
            ]);

            return redirect()->route('channels.index', ['business' => $businessId])
                ->with('error', 'حدث خطأ أثناء ربط حساب تيك توك: '.$e->getMessage());
        }
    }

    /**
     * Disconnect TikTok account.
     */
    public function disconnect(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        TiktokAccount::withoutGlobalScopes()->where('business_id', $business->id)->delete();

        return redirect()->route('channels.index', ['business' => $business->id])
            ->with('success', 'تم فصل حساب تيك توك بنجاح.');
    }

    /**
     * Authorize user access to the business.
     */
    protected function authorizeBusiness(Request $request, Business $business): void
    {
        $user = $request->user('web') ?: $request->user('team');

        if (! $user) {
            abort(403);
        }

        if ($request->user('web')) {
            if ($business->user_id !== $user->id && ! $user->isSuperAdmin()) {
                abort(403);
            }
        } elseif ($request->user('team')) {
            $hasAccess = $user->businesses()->where('businesses.id', $business->id)->exists();
            if (! $hasAccess || $user->role !== 'admin') {
                abort(403, 'غير مصرح لك بإدارة قنوات هذا المتجر.');
            }
        }
    }
}
