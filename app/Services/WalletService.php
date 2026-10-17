<?php

namespace App\Services;

use App\Models\BulkMessageTransaction;
use App\Models\BulkMessageWallet;
use App\Models\PaymentTransaction;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get predefined top-up packages.
     */
    public function getPackages(): array
    {
        return [
            [
                'id' => 'starter',
                'name' => 'باقة البداية (Starter)',
                'credits' => 500,
                'price' => 100.00,
                'currency' => 'EGP',
                'price_per_message' => '0.20 ج.م',
                'badge' => null,
            ],
            [
                'id' => 'growth',
                'name' => 'باقة النمو (Growth)',
                'credits' => 1500,
                'price' => 250.00,
                'currency' => 'EGP',
                'price_per_message' => '0.16 ج.م',
                'badge' => 'الأكثر طلباً',
            ],
            [
                'id' => 'pro',
                'name' => 'باقة المحترفين (Pro)',
                'credits' => 5000,
                'price' => 700.00,
                'currency' => 'EGP',
                'price_per_message' => '0.14 ج.م',
                'badge' => 'توفير 30%',
            ],
            [
                'id' => 'enterprise',
                'name' => 'باقة الشركات (Enterprise)',
                'credits' => 15000,
                'price' => 1800.00,
                'currency' => 'EGP',
                'price_per_message' => '0.12 ج.م',
                'badge' => 'أفضل قيمة',
            ],
        ];
    }

    /**
     * Credit/top-up user wallet.
     */
    public function creditWallet(
        User $user,
        float $amount,
        int $credits,
        ?PaymentTransaction $paymentTransaction = null,
        string $description = 'شحن رصيد رسائل جماعية'
    ): BulkMessageWallet {
        return DB::transaction(function () use ($user, $amount, $credits, $paymentTransaction, $description) {
            $wallet = BulkMessageWallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = BulkMessageWallet::create([
                    'user_id' => $user->id,
                    'balance' => 0.00,
                    'credits' => 0,
                    'currency' => 'EGP',
                ]);
            }

            $wallet->balance += $amount;
            $wallet->credits += $credits;
            $wallet->save();

            BulkMessageTransaction::create([
                'user_id' => $user->id,
                'business_id' => session('current_business_id'),
                'payment_transaction_id' => $paymentTransaction?->id,
                'type' => 'topup',
                'amount' => $amount,
                'credits' => $credits,
                'balance_after' => $wallet->balance,
                'credits_after' => $wallet->credits,
                'platform' => null,
                'description' => $description,
                'metadata' => [
                    'payment_ref' => $paymentTransaction?->reference_number,
                    'fawry_ref' => $paymentTransaction?->fawry_reference_number,
                ],
            ]);

            return $wallet;
        });
    }

    /**
     * Super Admin manual balance adjustment.
     */
    public function adjustBalance(
        User $user,
        float $amount,
        int $credits,
        string $reason,
        string $adminName
    ): BulkMessageWallet {
        return DB::transaction(function () use ($user, $amount, $credits, $reason, $adminName) {
            $wallet = $user->getOrCreateWallet();
            $wallet = BulkMessageWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $wallet->balance += $amount;
            $wallet->credits += $credits;

            if ($wallet->balance < 0) $wallet->balance = 0.00;
            if ($wallet->credits < 0) $wallet->credits = 0;

            $wallet->save();

            BulkMessageTransaction::create([
                'user_id' => $user->id,
                'business_id' => null,
                'payment_transaction_id' => null,
                'type' => 'manual_adjustment',
                'amount' => $amount,
                'credits' => $credits,
                'balance_after' => $wallet->balance,
                'credits_after' => $wallet->credits,
                'platform' => null,
                'description' => "تعديل يدوي بواسطة الأدمن ({$adminName}): {$reason}",
                'metadata' => [
                    'admin' => $adminName,
                    'reason' => $reason,
                ],
            ]);

            return $wallet;
        });
    }

    /**
     * Check if user has sufficient credits for an operation.
     */
    public function hasSufficientCredits(User $user, int $requiredCredits = 1): bool
    {
        $wallet = $user->getOrCreateWallet();
        return $wallet->credits >= $requiredCredits;
    }

    /**
     * Deduct credit for a sent message in a bulk campaign.
     * Throws an exception if user has insufficient credits.
     */
    public function deductCredit(
        User $user,
        int $businessId,
        string $platform,
        string $description,
        array $metadata = []
    ): BulkMessageWallet {
        return DB::transaction(function () use ($user, $businessId, $platform, $description, $metadata) {
            $wallet = BulkMessageWallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$wallet || $wallet->credits < 1) {
                throw new \RuntimeException('رصيد الرسائل غير كافٍ لإتمام عملية الإرسال.');
            }

            $wallet->credits -= 1;
            $wallet->save();

            BulkMessageTransaction::create([
                'user_id' => $user->id,
                'business_id' => $businessId,
                'payment_transaction_id' => null,
                'type' => 'usage',
                'amount' => 0.00,
                'credits' => -1,
                'balance_after' => $wallet->balance,
                'credits_after' => $wallet->credits,
                'platform' => $platform,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            return $wallet;
        });
    }
}

