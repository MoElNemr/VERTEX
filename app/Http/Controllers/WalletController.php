<?php

namespace App\Http\Controllers;

use App\Models\BulkMessageTransaction;
use App\Models\PaymentTransaction;
use App\Services\FawryService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected FawryService $fawryService
    ) {}

    /**
     * Display wallet balance, recharge packages, and history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user('web');
        $wallet = $user->getOrCreateWallet();

        $transactions = BulkMessageTransaction::where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Wallet/Index', [
            'wallet' => $wallet,
            'packages' => $this->walletService->getPackages(),
            'transactions' => $transactions,
            'fawryConfig' => [
                'merchant_code' => $this->fawryService->getConfig()['merchant_code'],
                'mode' => $this->fawryService->getConfig()['mode'],
            ],
        ]);
    }

    /**
     * Initiate Fawry checkout for wallet top-up.
     */
    public function topup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:10'],
            'credits' => ['required', 'integer', 'min:10'],
            'package_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user('web');

        $charge = $this->fawryService->createWalletTopupCharge(
            $user,
            (float) $validated['amount'],
            (int) $validated['credits'],
            $validated['package_name'] ?? 'باقة رسائل جماعية'
        );

        return response()->json([
            'status' => 'success',
            'charge' => $charge,
        ]);
    }

    /**
     * Simulate Fawry payment for sandbox testing.
     */
    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference_number' => ['required', 'exists:payment_transactions,reference_number'],
        ]);

        $transaction = PaymentTransaction::where('reference_number', $validated['reference_number'])->firstOrFail();

        $this->fawryService->handleSuccessfulPayment($transaction, 'SIM_' . rand(10000000, 99999999));

        return response()->json([
            'status' => 'success',
            'message' => 'تمت محاكاة شحن الرصيد بنجاح!',
        ]);
    }
}
