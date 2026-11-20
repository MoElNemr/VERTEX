<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    /**
     * Show Super Admin Master Login page.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user('web');

        if ($user && $user->is_super_admin) {
            return redirect()->route('superadmin.index');
        }

        return Inertia::render('Admin/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Authenticate Super Admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Strict Super Admin Verification
            if (! $user->is_super_admin) {
                RateLimiter::hit($throttleKey);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'غير مصرح لك بالوصول. هذه البوابة مخصصة لمالك المنصة فقط.',
                ]);
            }

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route('superadmin.index'));
        }

        RateLimiter::hit($throttleKey);

        return back()->withErrors([
            'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
        ]);
    }

    /**
     * Log out Super Admin.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'تم تسجيل الخروج من لوحة الإدارة العليا بنجاح.');
    }
}
