<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLedger extends Model
{
    protected $table = 'usage_ledger';

    // Immutable: no mass updates allowed
    protected $fillable = [
        'business_id',
        'user_id',
        'entry_type',
        'category',
        'amount',
        'credits',
        'currency',
        'description',
        'reference_type',
        'reference_id',
        'balance_after',
        'credits_after',
        'recorded_by',
        'reason',
    ];

    protected $casts = [
        'amount'        => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an immutable debit entry.
     */
    public static function debit(
        int $userId,
        float $amount,
        int $credits,
        string $category,
        string $description,
        array $meta = []
    ): self {
        return static::create(array_merge([
            'user_id'      => $userId,
            'entry_type'   => 'debit',
            'category'     => $category,
            'amount'       => $amount,
            'credits'      => $credits,
            'currency'     => 'EGP',
            'description'  => $description,
            'recorded_by'  => 'system',
        ], $meta));
    }

    /**
     * Record an immutable credit entry.
     */
    public static function credit(
        int $userId,
        float $amount,
        int $credits,
        string $category,
        string $description,
        array $meta = []
    ): self {
        return static::create(array_merge([
            'user_id'     => $userId,
            'entry_type'  => 'credit',
            'category'    => $category,
            'amount'      => $amount,
            'credits'     => $credits,
            'currency'    => 'EGP',
            'description' => $description,
            'recorded_by' => 'system',
        ], $meta));
    }
}
