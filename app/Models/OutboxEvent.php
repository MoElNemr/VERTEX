<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboxEvent extends Model
{
    protected $fillable = [
        'business_id',
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'next_attempt_at',
        'delivered_at',
        'last_error',
    ];

    protected $casts = [
        'payload'         => 'array',
        'next_attempt_at' => 'datetime',
        'delivered_at'    => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Commit a new outbox event atomically.
     */
    public static function commit(
        string $eventType,
        array $payload,
        ?int $businessId = null,
        string $aggregateType = null,
        int $aggregateId = null,
        int $maxAttempts = 3
    ): self {
        return static::create([
            'business_id'     => $businessId,
            'event_type'      => $eventType,
            'aggregate_type'  => $aggregateType,
            'aggregate_id'    => $aggregateId,
            'payload'         => $payload,
            'status'          => 'pending',
            'attempts'        => 0,
            'max_attempts'    => $maxAttempts,
            'next_attempt_at' => now(),
        ]);
    }

    public function markDelivered(): void
    {
        $this->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markFailed(string $error, int $backoffSeconds = 60): void
    {
        $attempts = $this->attempts + 1;
        $this->update([
            'status'          => $attempts >= $this->max_attempts ? 'failed' : 'pending',
            'attempts'        => $attempts,
            'last_error'      => $error,
            'next_attempt_at' => now()->addSeconds($backoffSeconds * $attempts),
        ]);
    }
}
