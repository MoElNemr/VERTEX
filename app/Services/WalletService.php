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
     * Get configured bulk message unit price in EGP.
     */
    public function getBulkMessagePrice(): float
    {
        return (float) SystemSetting::get('bulk_message_price', 0.20);
    }

    /**
     * Get configured retargeting message unit price in EGP.
     */
    public function getRetargetingMessagePrice(): float
    {
        return (float) SystemSetting::get('retargeting_message_price', 0.25);
    }

    /**
     * Get credits deducted per bulk message.
     */
    public function getBulkMessageCreditCost(): int
    {
        return max(1, (int) SystemSetting::get('bulk_message_credit_cost', 1));
    }

    /**
     * Get credits deducted per retargeting message.
     */
    public function getRetargetingMessageCreditCost(): int
    {
        return max(1, (int) SystemSetting::get('retargeting_message_credit_cost', 1));
    }

    /**
     * Get consolidated message pricing settings.
     */
    public function getMessagePricing(): array
    {
        return [
            'bulk' => [
                'price' => $this->getBulkMessagePrice(),
                'credit_cost' => $this->getBulkMessageCreditCost(),
                'currency' => 'EGP',
                'label' => 'رسائل الإرسال الجماعي (Bulk Sender)',
            ],
            'retargeting' => [
                'price' => $this->getRetargetingMessagePrice(),
                'credit_cost' => $this->getRetargetingMessageCreditCost(),
                'currency' => 'EGP',
                'label' => 'رسائل إعادة الاستهداف (Retargeting)',
            ],
        ];
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
     * Deduct credit for a sent message in a bulk or retargeting campaign.
     * Throws an exception if user has insufficient credits.
     */
    public function deductCredit(
        User $user,
        int $businessId,
        string $platform,
        string $description,
        array $metadata = [],
        int $creditCost = 1
    ): BulkMessageWallet {
        $cost = max(1, $creditCost);

        return DB::transaction(function () use ($user, $businessId, $platform, $description, $metadata, $cost) {
            $wallet = BulkMessageWallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$wallet || $wallet->credits < $cost) {
                throw new \RuntimeException("رصيد الرسائل غير كافٍ. المطلوب: {$cost} نقطة، المتاح: " . ($wallet?->credits ?? 0));
            }

            $wallet->credits -= $cost;
            $wallet->save();

            BulkMessageTransaction::create([
                'user_id' => $user->id,
                'business_id' => $businessId,
                'payment_transaction_id' => null,
                'type' => 'usage',
                'amount' => 0.00,
                'credits' => -$cost,
                'balance_after' => $wallet->balance,
                'credits_after' => $wallet->credits,
                'platform' => $platform,
                'description' => $description,
                'metadata' => array_merge($metadata, ['credit_cost' => $cost]),
            ]);

            return $wallet;
        });
    }
}


