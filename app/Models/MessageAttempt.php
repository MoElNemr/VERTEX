<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MessageAttempt extends Model
{
    protected $fillable = [
        'message_id',
        'business_id',
        'idempotency_key',
        'provider',
        'provider_message_id',
        'status',
        'error_category',
        'error_message',
        'cost',
        'submitted_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'cost' => 'decimal:4',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Generate a unique idempotency key for a send operation.
     */
    public static function makeIdempotencyKey(int $messageId, string $provider): string
    {
        return "msg_{$messageId}_{$provider}_" . Str::random(8);
    }
}
