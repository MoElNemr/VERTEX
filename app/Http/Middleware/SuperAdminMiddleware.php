<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard for the platform admin console (/admin/*).
 * Accepts ONLY the dedicated 'admin' guard (PlatformAdmin).
 * A regular user session — even with is_super_admin=true — cannot enter.
 */
class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if (! $admin) {
            // Preserve intended URL for post-login redirect
            return redirect()->route('admin.login')
                ->with('status', 'يرجى تسجيل الدخول للوصول إلى لوحة إدارة المنصة.');
        }

        if (! $admin->is_active) {
            auth('admin')->logout();
            $request->session()->invalidate();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'هذا الحساب موقوف. تواصل مع مالك المنصة.']);
        }

        return $next($request);
    }
}
