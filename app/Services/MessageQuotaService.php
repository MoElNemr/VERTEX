<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessageQuotaService
{
    /**
     * Check if user/owner has sufficient message quota on active subscription to send outbound messages.
     */
    public function canSend(User $owner, int $count = 1): bool
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
                'quota_resets_at' => Carbon::now()->addMonth(),
            ]);
            $sub->refresh();
        }

        if ($sub->messages_limit === null) {
            return true;
        }

        return max(0, $sub->messages_limit - $sub->messages_used) >= $count;
    }

    /**
     * Atomically consume quota for sent messages from subscription.
     * Strictly called AFTER external outbound API succeeds.
     */
    public function consumeQuota(User $owner, int $businessId, string $platform, int $count = 1, ?string $description = null): bool
    {
        if ($count <= 0) {
            return true;
        }

        if ($owner->isSuperAdmin()) {
            return true;
        }

        return DB::transaction(function () use ($owner, $count) {
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
                $sub->quota_resets_at = Carbon::now()->addMonth();
            }

            if ($sub->messages_limit !== null && ($sub->messages_limit - $sub->messages_used) < $count) {
                return false;
            }

            $sub->messages_used += $count;
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
            ];
        }

        if ($owner->isSuperAdmin()) {
            return [
                'limit' => null,
                'used' => 0,
                'remaining' => null,
                'resets_at' => null,
                'is_unlimited' => true,
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
            ];
        }

        if ($sub->quota_resets_at && $sub->quota_resets_at->isPast()) {
            $sub->update([
                'messages_used' => 0,
                'quota_resets_at' => Carbon::now()->addMonth(),
            ]);
            $sub->refresh();
        }

        $isUnlimited = $sub->messages_limit === null;
        $remaining = $isUnlimited ? null : max(0, $sub->messages_limit - $sub->messages_used);

        return [
            'limit' => $sub->messages_limit,
            'used' => (int) $sub->messages_used,
            'remaining' => $remaining,
            'resets_at' => $sub->quota_resets_at?->toDateString(),
            'is_unlimited' => $isUnlimited,
        ];
    }
}
