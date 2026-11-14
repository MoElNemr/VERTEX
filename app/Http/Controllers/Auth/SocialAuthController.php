<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\MetaOAuthService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function __construct(
        protected MetaOAuthService $metaOAuthService
    ) {}

    /**
     * Redirect guest user to Facebook for authentication.
     */
    public function redirectToFacebook(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        session(['fb_auth_state' => $state]);

        $redirectUri = route('auth.facebook.callback');
        $authUrl = $this->metaOAuthService->getAuthorizationUrl(
            $state,
            $redirectUri,
            MetaOAuthService::LOGIN_SCOPES
        );

        return redirect()->away($authUrl);
    }

    /**
     * Handle incoming Facebook OAuth callback for user authentication.
     */
    public function handleFacebookCallback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            $errorDescription = $request->query('error_description', $request->query('error', 'تم إلغاء تسجيل الدخول عبر فيسبوك.'));

            return redirect()->route('login')->with('error', $errorDescription);
        }

        $sessionState = session()->pull('fb_auth_state');
        $returnedState = $request->query('state');

        if (! $sessionState || ! $returnedState || ! hash_equals($sessionState, $returnedState)) {
            return redirect()->route('login')->with('error', 'فشل التحقق الأمني من جلسة تسجيل الدخول (CSRF). يرجى المحاولة مرة أخرى.');
        }

        $code = $request->query('code');
        if (! $code) {
            return redirect()->route('login')->with('error', 'لم يتم استلام رمز التفويض من فيسبوك.');
        }

        try {
            $redirectUri = route('auth.facebook.callback');
            $tokenData = $this->metaOAuthService->exchangeCodeForToken($code, $redirectUri);
            $userAccessToken = $tokenData['access_token'] ?? null;

            if (! $userAccessToken) {
                throw new Exception('Invalid access token response.');
            }

            $profile = $this->metaOAuthService->getUserProfile($userAccessToken);
            $fbId = (string) ($profile['id'] ?? '');
            $name = $profile['name'] ?? 'Facebook User';
            $email = $profile['email'] ?? "fb_{$fbId}@facebook.vertex.local";
            $avatar = $profile['picture']['data']['url'] ?? null;

            if (empty($fbId)) {
                throw new Exception('Facebook profile ID missing.');
            }

            $user = User::where('facebook_id', $fbId)->first();

            if (! $user) {
                // Check if user exists by email
                $user = User::where('email', $email)->first();

                if ($user) {
                    $user->update([
                        'facebook_id' => $fbId,
                        'avatar' => $user->avatar ?: $avatar,
                    ]);
                }
            }

            if (! $user) {
                $user = DB::transaction(function () use ($name, $email, $fbId, $avatar) {
                    $newUser = User::create([
                        'name' => $name,
                        'email' => $email,
                        'facebook_id' => $fbId,
                        'avatar' => $avatar,
                        'email_verified_at' => now(),
                        'password' => null,
                    ]);

                    $business = $newUser->businesses()->create([
                        'name' => $name.' Store',
                    ]);

                    $plan = Plan::where('is_active', true)->orderBy('price_monthly')->first();
                    if ($plan) {
                        $newUser->subscriptions()->create([
                            'plan_id' => $plan->id,
                            'status' => 'trial',
                            'starts_at' => now(),
                            'ends_at' => now()->addDays(14),
                        ]);
                    }

                    return $newUser;
                });
            }

            Auth::guard('web')->login($user, true);

            $business = $user->businesses()->first();
            if ($business) {
                session(['current_business_id' => $business->id]);
            }

            return redirect()->intended(route('dashboard'));
        } catch (\Throwable $e) {
            Log::error('Facebook social auth failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'حدث خطأ أثناء تسجيل الدخول عبر فيسبوك: '.$e->getMessage());
        }
    }
}
