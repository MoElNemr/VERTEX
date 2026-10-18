<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    protected $fillable = [
        'business_id',
        'contact_id',
        'channel',
        'purpose',
        'source',
        'proof',
        'captured_at',
        'withdrawn_at',
        'is_active',
    ];

    protected $casts = [
        'captured_at'   => 'datetime',
        'withdrawn_at'  => 'datetime',
        'is_active'     => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Check if a contact has active consent for a channel+purpose.
     */
    public static function hasConsent(int $businessId, int $contactId, string $channel, string $purpose = 'marketing'): bool
    {
        return static::where('business_id', $businessId)
            ->where('contact_id', $contactId)
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->where('is_active', true)
            ->whereNull('withdrawn_at')
            ->exists();
    }

    /**
     * Check if contact is suppressed for channel.
     */
    public static function isSuppressed(int $businessId, string $channel, string $identifier): bool
    {
        return SuppressionEntry::where('business_id', $businessId)
            ->where('channel', $channel)
            ->where('identifier', $identifier)
            ->exists();
    }

    public function withdraw(string $reason = null): void
    {
        $this->update([
            'is_active'    => false,
            'withdrawn_at' => now(),
        ]);
    }
}
