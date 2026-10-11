<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // تفعيل اشتراك تجريبي في الباقة الأساسية
        $defaultPlan = \App\Models\Plan::where('type', 'standard')->first();
        if ($defaultPlan) {
            $user->subscriptions()->create([
                'plan_id' => $defaultPlan->id,
                'status' => 'trial',
                'starts_at' => now(),
                'ends_at' => now()->addDays(14),
            ]);
        }

        // إنشاء بيزنس أولي افتراضي باسم المستخدم
        $business = $user->businesses()->create([
            'name' => $user->name . ' Store',
        ]);
        session(['current_business_id' => $business->id]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('businesses.index', absolute: false));
    }
}
