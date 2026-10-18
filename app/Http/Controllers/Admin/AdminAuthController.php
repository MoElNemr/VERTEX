<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\PlatformAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    /**
     * Show the platform admin login page.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user('admin')) {
            return redirect()->route('superadmin.index');
        }

        // Block regular user sessions from reaching admin login silently
        if ($request->user('web')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return Inertia::render('Admin/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Authenticate a platform admin via the dedicated 'admin' guard.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::guard('admin')->attempt($credentials, $remember)) {
            return back()->withErrors([
                'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ])->onlyInput('email');
        }

        /** @var PlatformAdmin $admin */
        $admin = Auth::guard('admin')->user();

        if (! $admin->is_active) {
            Auth::guard('admin')->logout();
            return back()->withErrors([
                'email' => 'هذا الحساب موقوف. تواصل مع مالك المنصة.',
            ])->onlyInput('email');
        }

        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        AuditEvent::log('admin.login', [
            'actor_type' => 'platform_admin',
            'actor_id'   => $admin->id,
            'after'      => ['role' => $admin->role],
        ], $request);

        return redirect()->intended(route('superadmin.index'));
    }

    /**
     * Log out the platform admin.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $admin = $request->user('admin');

        if ($admin) {
            AuditEvent::log('admin.logout', [
                'actor_type' => 'platform_admin',
                'actor_id'   => $admin->id,
            ], $request);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('status', 'تم تسجيل الخروج من لوحة إدارة المنصة بنجاح.');
    }
}
