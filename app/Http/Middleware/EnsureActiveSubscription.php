<?php

namespace App\Http\Middleware;

use App\Models\TeamMember;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     * Enforce that the user has an active/trial subscription before accessing platform features.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('team')->user()
            ?: Auth::guard('web')->user()
            ?: $request->user('team')
            ?: $request->user('web')
            ?: $request->user();

        // Unauthenticated guests pass freely (auth middleware handles them)
        if (! $user) {
            return $next($request);
        }

        if ($user instanceof User && $user->is_super_admin) {
            return $next($request);
        }

        // Exempt subscription checkout, callbacks, user profile, logout, webhooks, and meta callback
        if ($request->routeIs(
            'subscription.*',
            'profile.*',
            'logout',
            'team.logout',
            'webhooks.*',
            'meta.callback'
        )) {
            return $next($request);
        }

        if ($user instanceof TeamMember) {
            $owner = $user->owner;
            if (! $owner || ! $owner->activeSubscription()->exists()) {
                abort(403, 'اشتراك المتجر غير مفعل حالياً. يرجى من مالك الحساب تجديد الاشتراك.');
            }
        } elseif ($user instanceof User) {
            if (! $user->activeSubscription()->exists()) {
                return redirect()->route('subscription.index')->with(
                    'error',
                    'يرجى سداد الباقة المختارة لتفعيل حسابك والبدء في استخدام كافة خدمات المنصة.'
                );
            }
        }

        return $next($request);
    }
}
