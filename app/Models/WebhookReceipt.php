<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookReceipt extends Model
{
    protected $fillable = [
         'business_id',
        'provider',
        'provider_event_id',
        'webhook_type',
        'signature_result',
        'processing_status',
        'error_message',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Record a webhook receipt and check for duplicates.
     * Returns [receipt, isDuplicate].
     */
    public static function recordAndCheck(
        string $provider,
        ?string $eventId,
        ?int $businessId = null,
        string $webhookType = '',
        string $signatureResult = 'valid'
    ): array {
        if ($eventId) {
            $existing = static::where('provider', $provider)
                ->where('provider_event_id', $eventId)
                ->first();

            if ($existing) {
                return [$existing, true];
            }
        }

        $receipt = static::create([
            'business_id' => $businessId,
            'provider' => $provider,
            'provider_event_id' => $eventId,
            'webhook_type' => $webhookType,
            'signature_result' => $signatureResult,
            'processing_status' => 'pending',
            'received_at' => now(),
        ]);

        return [$receipt, false];
    }

    public function markProcessed(): void
    {
        $this->update([
            'processing_status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'error_message' => $error,
            'processed_at' => now(),
        ]);
    }
}
