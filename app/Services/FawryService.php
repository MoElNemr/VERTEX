<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FawryService
{
    /**
     * Get Fawry configuration from SystemSetting or .env.
     */
    public function getConfig(): array
    {
        $envMode = config('services.fawry.mode', env('FAWRY_MODE'));
        $settingMode = SystemSetting::get('fawry_mode');
        $defaultMode = app()->environment('production') ? 'live' : 'sandbox';

        // Link strictly to env variable if set, otherwise DB setting or environment default
        $mode = $envMode ?: ($settingMode ?: $defaultMode);

        // In production, force live unless explicitly overridden by FAWRY_MODE in .env
        if (app()->environment('production') && $envMode !== 'sandbox') {
            $mode = 'live';
        }

        return [
            'merchant_code' => SystemSetting::get('fawry_merchant_code', config('services.fawry.merchant_code', env('FAWRY_MERCHANT_CODE', '1014316'))),
            'security_key' => SystemSetting::get('fawry_security_key', config('services.fawry.security_key', env('FAWRY_SECURITY_KEY', 'default_test_key'))),
            'merchant_wallet' => SystemSetting::get('fawry_merchant_wallet', config('services.fawry.merchant_wallet', env('FAWRY_MERCHANT_WALLET', ''))),
            'mode' => $mode,
        ];
    }

    /**
     * Get Fawry base API URL.
     */
    public function getBaseUrl(): string
    {
        $config = $this->getConfig();

        return $config['mode'] === 'live'
            ? 'https://www.atfawry.com'
            : 'https://atfawry.fawrystag.com';
    }

    /**
     * Create payment charge for a plan subscription.
     */
    public function createSubscriptionCharge(User $user, Plan $plan, string $billingCycle = 'monthly'): array
    {
        $config = $this->getConfig();
        $merchantCode = $config['merchant_code'];
        $securityKey = $config['security_key'];

        $amount = $billingCycle === 'yearly' ? (float) $plan->price_yearly : (float) $plan->price_monthly;
        $merchantRefNum = 'SUB_'.$user->id.'_'.$plan->id.'_'.time().'_'.rand(100, 999);
        $returnUrl = url('/subscription/callback?ref='.$merchantRefNum);

        // Record pending transaction
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'reference_number' => $merchantRefNum,
            'type' => 'subscription',
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'currency' => 'EGP',
            'status' => 'pending',
            'payment_method' => 'fawry',
            'payment_data' => [
                'plan_name' => $plan->name,
                'billing_cycle' => $billingCycle,
                'user_email' => $user->email,
            ],
        ]);

        $itemCode = 'PLAN_'.$plan->id;
        $quantity = 1;
        $formattedPrice = number_format($amount, 2, '.', '');

        // Fawry signature calculation
        $signatureString = $merchantCode.$merchantRefNum.$user->id.$returnUrl.$itemCode.$quantity.$formattedPrice.$securityKey;
        $signature = hash('sha256', $signatureString);

        $chargePayload = [
            'merchantCode' => $merchantCode,
            'merchantRefNum' => $merchantRefNum,
            'customerProfileId' => (string) $user->id,
            'customerName' => $user->name,
            'customerEmail' => $user->email,
            'customerMobile' => '',
            'paymentMethod' => 'ALL',
            'amount' => $amount,
            'currencyCode' => 'EGP',
            'description' => "اشتراك باقة {$plan->name} ({$billingCycle})",
            'chargeItems' => [
                [
                    'itemId' => $itemCode,
                    'description' => "باقة {$plan->name}",
                    'price' => $amount,
                    'quantity' => 1,
                ],
            ],
            'returnUrl' => $returnUrl,
            'signature' => $signature,
        ];

        // Call Fawry API to initialize charge
        $fawryRefNumber = null;
        $checkoutUrl = null;

        try {
            $apiUrl = $this->getBaseUrl().'/ECommerceWeb/Fawry/payments/charge';
            $response = Http::timeout(8)->post($apiUrl, $chargePayload);

            if ($response->successful()) {
                $resData = $response->json();
                $fawryRefNumber = $resData['fawryRefNumber'] ?? null;
                $checkoutUrl = $resData['checkoutUrl'] ?? null;

                if ($fawryRefNumber) {
                    $transaction->update(['fawry_reference_number' => $fawryRefNumber]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Fawry API charge connection error: '.$e->getMessage());
        }

        // Fallback / Sandbox Reference Code generation if external endpoint is offline or sandbox testing
        if (! $fawryRefNumber) {
            $fawryRefNumber = '98'.rand(10000000, 99999999);
            $transaction->update(['fawry_reference_number' => $fawryRefNumber]);
        }

        return [
            'transaction_id' => $transaction->id,
            'reference_number' => $merchantRefNum,
            'fawry_reference_number' => $fawryRefNumber,
            'amount' => $amount,
            'currency' => 'EGP',
            'checkout_url' => $checkoutUrl,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'billing_cycle' => $billingCycle,
            ],
            'fawry_mode' => $config['mode'],
            'merchant_code' => $merchantCode,
        ];
    }

    /**
     * Verify incoming Fawry IPN webhook signature.
     */
    public function verifyWebhookSignature(array $payload): bool
    {
        $config = $this->getConfig();
        $securityKey = $config['security_key'];

        $merchantCode = $payload['merchantCode'] ?? $config['merchant_code'];
        $merchantRefNum = $payload['merchantRefNum'] ?? ($payload['orderNumber'] ?? '');
        $fawryRefNumber = $payload['fawryRefNumber'] ?? '';
        $paymentAmount = isset($payload['paymentAmount']) ? number_format((float) $payload['paymentAmount'], 2, '.', '') : '';
        $orderStatus = $payload['orderStatus'] ?? '';
        $receivedSignature = $payload['messageSignature'] ?? '';

        if (empty($receivedSignature)) {
            return false;
        }

        $expectedString = $merchantCode.$merchantRefNum.$fawryRefNumber.$paymentAmount.$orderStatus.$securityKey;
        $expectedSignature = hash('sha256', $expectedString);

        return hash_equals(strtolower($expectedSignature), strtolower($receivedSignature));
    }

    /**
     * Process successful payment transaction.
     */
    public function handleSuccessfulPayment(PaymentTransaction $transaction, ?string $fawryRefNumber = null): bool
    {
        if ($transaction->status === 'paid') {
            return true;
        }

        $transaction->update([
            'status' => 'paid',
            'fawry_reference_number' => $fawryRefNumber ?: $transaction->fawry_reference_number,
            'paid_at' => now(),
        ]);

        // If subscription payment, activate user subscription
        if ($transaction->type === 'subscription' && $transaction->plan_id) {
            $user = $transaction->user;
            $plan = $transaction->plan;

            if ($user && $plan) {
                $duration = $transaction->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth();

                $user->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => $duration,
                ]);
            }
        }

        // If bulk message wallet top-up, credit user wallet
        if ($transaction->type === 'bulk_credit') {
            $user = $transaction->user;
            $credits = (int) ($transaction->payment_data['credits'] ?? ($transaction->metadata['credits'] ?? 0));
            $amount = (float) $transaction->amount;

            if ($user) {
                $walletService = app(WalletService::class);
                $walletService->creditWallet(
                    $user,
                    $amount,
                    $credits,
                    $transaction,
                    "شحن رصيد رسائل فوري - {$credits} رسالة"
                );
            }
        }

        return true;
    }

    /**
     * Create payment charge for bulk message wallet top-up.
     */
    public function createWalletTopupCharge(User $user, float $amount, int $credits, string $packageName = 'باقة رسائل'): array
    {
        $config = $this->getConfig();
        $merchantCode = $config['merchant_code'];
        $securityKey = $config['security_key'];

        $merchantRefNum = 'TOPUP_'.$user->id.'_'.time().'_'.rand(100, 999);
        $returnUrl = url('/wallet?ref='.$merchantRefNum);

        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'plan_id' => null,
            'reference_number' => $merchantRefNum,
            'type' => 'bulk_credit',
            'billing_cycle' => null,
            'amount' => $amount,
            'currency' => 'EGP',
            'status' => 'pending',
            'payment_method' => 'fawry',
            'payment_data' => [
                'credits' => $credits,
                'package_name' => $packageName,
                'user_email' => $user->email,
            ],
        ]);

        $itemCode = 'BULK_'.$credits;
        $quantity = 1;
        $formattedPrice = number_format($amount, 2, '.', '');

        $signatureString = $merchantCode.$merchantRefNum.$user->id.$returnUrl.$itemCode.$quantity.$formattedPrice.$securityKey;
        $signature = hash('sha256', $signatureString);

        $chargePayload = [
            'merchantCode' => $merchantCode,
            'merchantRefNum' => $merchantRefNum,
            'customerProfileId' => (string) $user->id,
            'customerName' => $user->name,
            'customerEmail' => $user->email,
            'paymentMethod' => 'ALL',
            'amount' => $amount,
            'currencyCode' => 'EGP',
            'description' => "شحن رصيد إرسال جماعي ({$credits} رسالة)",
            'chargeItems' => [
                [
                    'itemId' => $itemCode,
                    'description' => "شحن {$credits} رسالة",
                    'price' => $amount,
                    'quantity' => 1,
                ],
            ],
            'returnUrl' => $returnUrl,
            'signature' => $signature,
        ];

        $fawryRefNumber = null;
        $checkoutUrl = null;

        try {
            $apiUrl = $this->getBaseUrl().'/ECommerceWeb/Fawry/payments/charge';
            $response = Http::timeout(8)->post($apiUrl, $chargePayload);

            if ($response->successful()) {
                $resData = $response->json();
                $fawryRefNumber = $resData['fawryRefNumber'] ?? null;
                $checkoutUrl = $resData['checkoutUrl'] ?? null;

                if ($fawryRefNumber) {
                    $transaction->update(['fawry_reference_number' => $fawryRefNumber]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Fawry API wallet charge connection error: '.$e->getMessage());
        }

        if (! $fawryRefNumber) {
            $fawryRefNumber = '98'.rand(10000000, 99999999);
            $transaction->update(['fawry_reference_number' => $fawryRefNumber]);
        }

        return [
            'transaction_id' => $transaction->id,
            'reference_number' => $merchantRefNum,
            'fawry_reference_number' => $fawryRefNumber,
            'amount' => $amount,
            'credits' => $credits,
            'currency' => 'EGP',
            'checkout_url' => $checkoutUrl,
            'fawry_mode' => $config['mode'],
            'merchant_code' => $merchantCode,
        ];
    }
}
