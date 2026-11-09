<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\FacebookAccount;
use App\Models\InstagramAccount;
use App\Services\MetaOAuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaOAuthController extends Controller
{
    public function __construct(
        protected MetaOAuthService $metaService
    ) {}

    /**
     * Step 1: Redirect user to Meta OAuth dialog.
     */
    public function redirectToMeta(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $state = Str::random(40);

        session([
            'current_business_id' => $business->id,
            'meta_oauth_state' => $state,
            'meta_oauth_business_id' => $business->id,
        ]);

        $dialogUrl = $this->metaService->getAuthorizationUrl($state);

        return redirect()->away($dialogUrl);
    }

    /**
     * Step 2: Handle OAuth callback from Meta.
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $businessId = session('meta_oauth_business_id') ?? session('current_business_id');

        if (! $businessId) {
            return redirect()->route('businesses.index')
                ->with('error', 'OAuth session expired or invalid business context.');
        }

        $business = Business::find($businessId);
        if (! $business) {
            return redirect()->route('businesses.index')
                ->with('error', 'Business not found.');
        }

        // Catch user-cancellation or errors from Meta query params
        if ($request->has('error') || $request->has('error_code') || $request->has('error_message')) {
            $errorDesc = $request->input('error_description')
                ?? $request->input('error_message')
                ?? $request->input('error')
                ?? ('Error code: '.$request->input('error_code'));

            Log::warning('Meta OAuth authorization rejected or cancelled', [
                'error' => $request->all(),
                'business_id' => $businessId,
            ]);

            return redirect()->route('channels.index', ['business' => $businessId])
                ->with('error', 'تم إلغاء أو رفض عملية الربط عبر ميتا: '.$errorDesc);
        }

        // Verify CSRF state byte-for-byte
        $sessionState = session('meta_oauth_state');
        $returnedState = (string) $request->input('state');

        if (empty($sessionState) || empty($returnedState) || ! hash_equals($sessionState, $returnedState)) {
            Log::warning('Meta OAuth state mismatch', [
                'session_state' => $sessionState,
                'returned_state' => $returnedState,
            ]);
            abort(403, 'فشلت عملية التحقق الأمني من الـ state. يرجى إعادة المحاولة.');
        }

        $code = (string) $request->input('code');
        if (empty($code)) {
            return redirect()->route('channels.index', ['business' => $businessId])
                ->with('error', 'لم يتم استلام رمز المصادقة من ميتا.');
        }

        try {
            // 1. Authorization Code -> Short-Lived User Token (~1-2 hours)
            $shortLivedData = $this->metaService->exchangeCodeForToken($code);
            $shortLivedToken = $shortLivedData['access_token'];

            // 2. Short-Lived Token -> Long-Lived User Token (~60 days)
            $longLivedData = $this->metaService->exchangeForLongLivedToken($shortLivedToken);
            $longLivedToken = $longLivedData['access_token'];
            $expiresIn = $longLivedData['expires_in'] ?? (60 * 86400);
            $tokenExpiresAt = Carbon::now()->addSeconds($expiresIn);

            // Fetch Meta admin profile identity
            $userProfile = $this->metaService->getUserProfile($longLivedToken);
            $metaUserId = (string) ($userProfile['id'] ?? '');

            // 3. Discover manageable pages & linked Instagram accounts
            $accounts = $this->metaService->getManageableAccounts($longLivedToken);

            if (empty($accounts)) {
                return redirect()->route('channels.index', ['business' => $businessId])
                    ->with('error', 'لم يتم العثور على أي صفحات فيسبوك تملك صلاحيات إدارتها في هذا الحساب.');
            }

            // Store in temporary session memory
            session([
                'meta_oauth_accounts' => $accounts,
                'meta_user_id' => $metaUserId,
                'meta_user_name' => $userProfile['name'] ?? null,
                'meta_token_expires_at' => $tokenExpiresAt->toDateTimeString(),
                'meta_granted_scopes' => $this->metaService->getScopes(),
            ]);

            return redirect()->route('channels.index', [
                'business' => $businessId,
                'select_meta_asset' => 1,
            ])->with('success', 'تم التحقق من حساب ميتا بنجاح. يرجى اختيار الصفحة المطلوب ربطها.');
        } catch (Exception $e) {
            Log::error('Meta OAuth callback pipeline exception', [
                'error' => $e->getMessage(),
                'business_id' => $businessId,
            ]);

            return redirect()->route('channels.index', ['business' => $businessId])
                ->with('error', 'حدث خطأ أثناء الاتصال بميتا: '.$e->getMessage());
        }
    }

    /**
     * Step 3: Persist selected Facebook Page and linked Instagram Business account.
     */
    public function persistAsset(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        $validated = $request->validate([
            'page_id' => ['required', 'string'],
            'connect_instagram' => ['nullable', 'boolean'],
        ]);

        $pageId = $validated['page_id'];
        $connectInstagram = (bool) ($validated['connect_instagram'] ?? false);

        $accounts = session('meta_oauth_accounts');
        if (! is_array($accounts) || empty($accounts)) {
            return redirect()->route('channels.index', ['business' => $business->id])
                ->with('error', 'انتهت صلاحية جلسة الاختيار. يرجى تسجيل الدخول بحساب ميتا مجددًا.');
        }

        // Find selected page in authorized accounts session
        $selectedPage = null;
        foreach ($accounts as $acc) {
            if (isset($acc['id']) && (string) $acc['id'] === (string) $pageId) {
                $selectedPage = $acc;
                break;
            }
        }

        if (! $selectedPage) {
            return redirect()->route('channels.index', ['business' => $business->id])
                ->with('error', 'الصفحة المختارة غير موجودة ضمن الحسابات المصرح بها.');
        }

        $metaUserId = session('meta_user_id');
        $tokenExpiresAt = session('meta_token_expires_at');
        $grantedScopes = session('meta_granted_scopes') ?? MetaOAuthService::SCOPES;

        $pageAccessToken = $selectedPage['access_token'];
        $pageAvatar = $selectedPage['picture']['data']['url'] ?? null;

        DB::transaction(function () use (
            $business,
            $selectedPage,
            $pageAccessToken,
            $pageAvatar,
            $metaUserId,
            $tokenExpiresAt,
            $grantedScopes,
            $connectInstagram
        ) {
            // Update or create Facebook account
            $business->facebookAccount()->updateOrCreate(
                ['business_id' => $business->id],
                [
                    'page_id' => $selectedPage['id'],
                    'page_name' => $selectedPage['name'] ?? 'Facebook Page',
                    'meta_user_id' => $metaUserId,
                    'page_access_token' => $pageAccessToken,
                    'token_type' => 'oauth',
                    'token_expires_at' => $tokenExpiresAt,
                    'avatar_url' => $pageAvatar,
                    'granted_scopes' => $grantedScopes,
                    'status' => 'connected',
                ]
            );

            // Connect Instagram if requested and linked account exists
            if ($connectInstagram && ! empty($selectedPage['instagram_business_account'])) {
                $ig = $selectedPage['instagram_business_account'];

                $business->instagramAccount()->updateOrCreate(
                    ['business_id' => $business->id],
                    [
                        'instagram_business_id' => $ig['id'],
                        'username' => $ig['username'] ?? null,
                        'meta_user_id' => $metaUserId,
                        'linked_facebook_page_id' => $selectedPage['id'],
                        'access_token' => $pageAccessToken, // Permanent Page Token accesses IG
                        'token_type' => 'oauth',
                        'token_expires_at' => $tokenExpiresAt,
                        'avatar_url' => $ig['profile_picture_url'] ?? null,
                        'granted_scopes' => $grantedScopes,
                        'status' => 'connected',
                    ]
                );
            }
        });

        // Auto-subscribe page to webhook events
        $this->metaService->subscribePageWebhook($selectedPage['id'], $pageAccessToken);

        // Clean up temporary OAuth session data
        session()->forget([
            'meta_oauth_state',
            'meta_oauth_business_id',
            'meta_oauth_accounts',
            'meta_user_id',
            'meta_user_name',
            'meta_token_expires_at',
            'meta_granted_scopes',
        ]);

        return redirect()->route('channels.index', ['business' => $business->id])
            ->with('success', 'تم ربط القنوات المختارة عبر ميتا بنجاح!');
    }

    /**
     * Step 4: Disconnect channel (facebook, instagram, or both).
     */
    public function disconnectChannel(Request $request, Business $business, string $channel): RedirectResponse
    {
        $this->authorizeBusiness($request, $business);

        match ($channel) {
            'facebook' => $business->facebookAccount()?->delete(),
            'instagram' => $business->instagramAccount()?->delete(),
            'both', 'meta' => DB::transaction(function () use ($business) {
                $business->facebookAccount()?->delete();
                $business->instagramAccount()?->delete();
            }),
            default => null,
        };

        return redirect()->route('channels.index', ['business' => $business->id])
            ->with('success', 'تم فصل القناة بنجاح.');
    }

    /**
     * Step 5: Meta Compliance Data Deletion Callback.
     */
    public function dataDeletion(Request $request): JsonResponse
    {
        $signedRequest = (string) $request->input('signed_request');

        if (empty($signedRequest)) {
            return response()->json(['error' => 'Missing signed_request parameter.'], 400);
        }

        $payload = $this->metaService->parseSignedRequest($signedRequest);
        if (! $payload || empty($payload['user_id'])) {
            return response()->json(['error' => 'Invalid signed_request signature.'], 403);
        }

        $metaUserId = (string) $payload['user_id'];

        // Remove tokens and accounts associated with this Meta user
        DB::transaction(function () use ($metaUserId) {
            FacebookAccount::withoutGlobalScopes()
                ->where('meta_user_id', $metaUserId)
                ->delete();

            InstagramAccount::withoutGlobalScopes()
                ->where('meta_user_id', $metaUserId)
                ->delete();
        });

        $confirmationCode = hash('sha256', $metaUserId.'_'.time());

        $statusUrl = route('meta.deletion-status', ['code' => $confirmationCode]);

        return response()->json([
            'url' => $statusUrl,
            'confirmation_code' => $confirmationCode,
        ]);
    }

    /**
     * Deletion status confirmation check.
     */
    public function deletionStatus(string $code): JsonResponse
    {
        return response()->json([
            'status' => 'completed',
            'confirmation_code' => $code,
            'message' => 'All tokens and accounts associated with your Meta identity have been successfully purged.',
        ]);
    }

    /**
     * Helper to verify business authorization and tenant scope.
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

        if (! $hasAccess) {
            abort(403, 'غير مصرح لك بالوصول لهذا البيزنس.');
        }
    }
}
