<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Services\FawryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        protected FawryService $fawryService
    ) {}

    /**
     * Display current subscription and available plans.
     */
    public function index(Request $request): Response
    {
        $user = $request->user('web');
        $activeSubscription = $user->activeSubscription()->with('plan')->first();
        $plans = Plan::where('is_active', true)->get();
        $recentTransactions = PaymentTransaction::where('user_id', $user->id)
            ->with('plan:id,name')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Subscription/Index', [
            'activeSubscription' => $activeSubscription,
            'plans' => $plans,
            'recentTransactions' => $recentTransactions,
            'fawryConfig' => [
                'merchant_code' => $this->fawryService->getConfig()['merchant_code'],
                'mode' => $this->fawryService->getConfig()['mode'],
            ],
        ]);
    }

    /**
     * Initiate Fawry checkout for a plan.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $user = $request->user('web');
        $plan = Plan::findOrFail($validated['plan_id']);

        $chargeData = $this->fawryService->createSubscriptionCharge(
            $user,
            $plan,
            $validated['billing_cycle']
        );

        return response()->json([
            'status' => 'success',
            'charge' => $chargeData,
        ]);
    }

    /**
     * Sandbox simulation for immediate testing.
     */
    public function simulatePayment(Request $request): JsonResponse
    {
        if ($this->fawryService->getConfig()['mode'] !== 'sandbox') {
            return response()->json([
                'status' => 'error',
                'message' => 'محاكاة السداد غير متاحة في بيئة العمل الحية.',
            ], 403);
        }

        $validated = $request->validate([
            'reference_number' => ['required', 'exists:payment_transactions,reference_number'],
        ]);

        $transaction = PaymentTransaction::where('reference_number', $validated['reference_number'])->firstOrFail();

        $this->fawryService->handleSuccessfulPayment($transaction, 'SIM_'.rand(10000000, 99999999));

        return response()->json([
            'status' => 'success',
            'message' => 'تمت محاكاة سداد فوري وتفعيل الاشتراك بنجاح!',
        ]);
    }
}
