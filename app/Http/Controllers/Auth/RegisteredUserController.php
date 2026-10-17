<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\FawryService;
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
    public function __construct(
        protected FawryService $fawryService
    ) {}

    /**
     * Display the registration view with selectable plans.
     */
    public function create(): Response
    {
        $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();

        return Inertia::render('Auth/Register', [
            'plans' => $plans,
        ]);
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
            'plan_id' => 'nullable|exists:plans,id',
            'billing_cycle' => 'nullable|in:monthly,yearly',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $planId = $request->plan_id ?: (Plan::where('type', 'standard')->value('id') ?: Plan::value('id'));
        $selectedPlan = $planId ? Plan::find($planId) : null;
        if (!$selectedPlan) {
            $selectedPlan = Plan::firstOrCreate(
                ['type' => 'standard'],
                [
                    'name' => 'Standard Unlimited',
                    'price_monthly' => 0.00,
                    'price_yearly' => 0.00,
                    'is_active' => true,
                ]
            );
        }
        $billingCycle = $request->billing_cycle ?: 'monthly';
        $price = ($billingCycle === 'yearly' ? (float) $selectedPlan->price_yearly : (float) $selectedPlan->price_monthly);

        // Create initial default business
        $business = $user->businesses()->create([
            'name' => $user->name . ' Store',
        ]);
        session(['current_business_id' => $business->id]);

        event(new Registered($user));
        Auth::login($user);

        // If free or trial plan
        if ($price <= 0 || $selectedPlan->type === 'standard') {
            $user->subscriptions()->create([
                'plan_id' => $selectedPlan->id,
                'status' => 'trial',
                'starts_at' => now(),
                'ends_at' => now()->addDays(14),
            ]);

            return redirect()->route('businesses.index')->with('success', 'تم إنشاء حسابك وتفعيل الفترة التجريبية بنجاح!');
        }

        // Paid plan: create pending subscription and generate Fawry charge
        $charge = $this->fawryService->createSubscriptionCharge($user, $selectedPlan, $billingCycle);

        return redirect()->route('subscription.index')->with([
            'success' => 'تم إنشاء حسابك بنجاح. يرجى إتمام السداد عبر فوري لتفعيل باقتك المختارة.',
            'pendingCharge' => $charge,
        ]);
    }
}
