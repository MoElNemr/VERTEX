<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeamAuthenticatedSessionController extends Controller
{
    /**
     * Display team member login view.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('team')->check()) {
            return redirect()->route('inbox.index');
        }

        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/TeamLogin', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle incoming team member authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $teamMember = TeamMember::where('email', $credentials['email'])->first();

        if ($teamMember && !$teamMember->is_active) {
            throw ValidationException::withMessages([
                'email' => 'حسابك معطل حالياً. يرجى التواصل مع إدارة المتجر.',
            ]);
        }

        if (!Auth::guard('team')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة. يرجى التأكد من البريد وكلمة المرور.',
            ]);
        }

        $request->session()->regenerate();

        // تعيين أول متجر متاح للعضو كالبيزنس النشط
        $user = Auth::guard('team')->user();

        // التحقق من تفعيل البريد الإلكتروني
        if (!$user->hasVerifiedEmail()) {
            // إرسال رابط التحقق إذا لم يُرسل من قبل
            $user->sendEmailVerificationNotification();

            // تسجيل الخروج ومطالبته بالتحقق أولاً
            Auth::guard('team')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('team.login')->with(
                'status',
                'تم إرسال رابط تفعيل البريد الإلكتروني إلى ' . $user->email . ' — يرجى التحقق منه قبل تسجيل الدخول.'
            );
        }

        $firstBusiness = $user->businesses()->first();

        if ($firstBusiness) {
            session(['current_business_id' => $firstBusiness->id]);
        }

        return redirect()->intended(route('inbox.index', absolute: false));
    }

    /**
     * Destroy team member authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('team')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('team.login');
    }
}
