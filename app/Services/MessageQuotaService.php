<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessageQuotaService
{
    /**
     * Check if user/owner has sufficient message quota on active subscription.
     */
    public function canSend(User $owner, int $count = 1, bool $isAi = false): bool
    {
        if ($owner->isSuperAdmin()) {
            return true;
        }

        $sub = $owner->activeSubscription;
        if (! $sub) {
            return false;
        }

        if ($sub->quota_resets_at && $sub->quota_resets_at->isPast()) {
            $sub->update([
                'messages_used' => 0,
                'ai_messages_used' => 0,
                'quota_resets_at' => Carbon::now()->addMonth(),
            ]);
            $sub->refresh();
        }

        if ($isAi) {
            if ($sub->ai_messages_limit === null) {
                return true;
            }

            return max(0, $sub->ai_messages_limit - (int) $sub->ai_messages_used) >= $count;
        }

        if ($sub->messages_limit === null) {
            return true;
        }

        return max(0, $sub->messages_limit - (int) $sub->messages_used) >= $count;
    }

    /**
     * Specific check for AI message quota.
     */
    public function canSendAi(User $owner, int $count = 1): bool
    {
        return $this->canSend($owner, $count, true);
    }

    /**
     * Atomically consume quota for sent messages from subscription.
     * Strictly called AFTER external outbound API succeeds.
     */
    public function consumeQuota(
        User $owner,
        int $businessId,
        string $platform,
        int $count = 1,
        ?string $description = null,
        bool $isAi = false
    ): bool {
        if ($count <= 0) {
            return true;
        }

        if ($owner->isSuperAdmin()) {
            return true;
        }

        return DB::transaction(function () use ($owner, $count, $isAi) {
            $sub = Subscription::where('user_id', $owner->id)
                ->whereIn('status', ['active', 'trial'])
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $sub) {
                return false;
            }

            if ($sub->quota_resets_at && $sub->quota_resets_at->isPast()) {
                $sub->messages_used = 0;
                $sub->ai_messages_used = 0;
                $sub->quota_resets_at = Carbon::now()->addMonth();
            }

            if ($isAi) {
                if ($sub->ai_messages_limit !== null && ($sub->ai_messages_limit - (int) $sub->ai_messages_used) < $count) {
                    return false;
                }

                $sub->ai_messages_used = (int) $sub->ai_messages_used + $count;
            } else {
                if ($sub->messages_limit !== null && ($sub->messages_limit - (int) $sub->messages_used) < $count) {
                    return false;
                }

                $sub->messages_used = (int) $sub->messages_used + $count;
            }

            $sub->save();

            return true;
        });
    }

    /**
     * Get user quota summary for Inertia and UI.
     *
     * @return array<string, mixed>
     */
    public function getQuotaSummary(?User $owner): array
    {
        if (! $owner) {
            return [
                'limit' => null,
                'used' => 0,
                'remaining' => null,
                'resets_at' => null,
                'is_unlimited' => false,
                'ai_limit' => null,
                'ai_used' => 0,
                'ai_remaining' => null,
                'ai_is_unlimited' => false,
            ];
        }

        if ($owner->isSuperAdmin()) {
            return [
                'limit' => null,
                'used' => 0,
                'remaining' => null,
                'resets_at' => null,
                'is_unlimited' => true,
                'ai_limit' => null,
                'ai_used' => 0,
                'ai_remaining' => null,
                'ai_is_unlimited' => true,
            ];
        }

        $sub = $owner->activeSubscription;
        if (! $sub) {
            return [
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
                'resets_at' => null,
                'is_unlimited' => false,
                'ai_limit' => 0,
                'ai_used' => 0,
                'ai_remaining' => 0,
                'ai_is_unlimited' => false,
            ];
        }

        if ($sub->quota_resets_at && $sub->quota_resets_at->isPast()) {
            $sub->update([
                'messages_used' => 0,
                'ai_messages_used' => 0,
                'quota_resets_at' => Carbon::now()->addMonth(),
            ]);
            $sub->refresh();
        }

        $isUnlimited = $sub->messages_limit === null;
        $remaining = $isUnlimited ? null : max(0, $sub->messages_limit - (int) $sub->messages_used);

        $aiIsUnlimited = $sub->ai_messages_limit === null;
        $aiRemaining = $aiIsUnlimited ? null : max(0, $sub->ai_messages_limit - (int) $sub->ai_messages_used);

        return [
            'limit' => $sub->messages_limit,
            'used' => (int) $sub->messages_used,
            'remaining' => $remaining,
            'resets_at' => $sub->quota_resets_at?->toDateString(),
            'is_unlimited' => $isUnlimited,
            'ai_limit' => $sub->ai_messages_limit,
            'ai_used' => (int) $sub->ai_messages_used,
            'ai_remaining' => $aiRemaining,
            'ai_is_unlimited' => $aiIsUnlimited,
        ];
    }
}
