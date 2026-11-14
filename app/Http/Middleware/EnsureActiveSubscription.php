<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     * Enforce that the user has an active/trial subscription before accessing platform features.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        // Unauthenticated guests, team guard members, and super admins pass freely
        if (! $user || $user->is_super_admin) {
            return $next($request);
        }

        // Exempt subscription checkout, callbacks, user profile, logout, webhooks, and meta callback
        if ($request->routeIs(
            'subscription.*',
            'profile.*',
            'logout',
            'webhooks.*',
            'meta.callback'
        )) {
            return $next($request);
        }

        // If user has no active subscription (active or trial), block and redirect to payment
        if (! $user->activeSubscription()->exists()) {
            return redirect()->route('subscription.index')->with(
                'error',
                'يرجى سداد الباقة المختارة لتفعيل حسابك والبدء في استخدام كافة خدمات المنصة.'
            );
        }

        return $next($request);
    }
}
