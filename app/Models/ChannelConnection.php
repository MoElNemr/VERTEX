<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelConnection extends Model
{
    protected $fillable = [
        'business_id',
        'provider',
        'provider_account_id',
        'display_name',
        'status',
        'capabilities',
        'credential_reference',
        'last_health_check_at',
        'health_status',
        'health_message',
        'connected_at',
        'disconnected_at',
        'token_expires_at',
    ];

    protected $casts = [
        'capabilities'         => 'array',
        'credential_reference' => 'array',
        'last_health_check_at' => 'datetime',
        'connected_at'         => 'datetime',
        'disconnected_at'      => 'datetime',
        'token_expires_at'     => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function markConnected(string $displayName = null, array $capabilities = []): void
    {
        $this->update([
            'status'               => 'connected',
            'health_status'        => 'healthy',
            'display_name'         => $displayName ?? $this->display_name,
            'capabilities'         => $capabilities ?: $this->capabilities,
            'connected_at'         => now(),
            'disconnected_at'      => null,
            'last_health_check_at' => now(),
            'health_message'       => null,
        ]);
    }

    public function markDisconnected(string $reason = null): void
    {
        $this->update([
            'status'          => 'disconnected',
            'health_status'   => 'unknown',
            'disconnected_at' => now(),
            'health_message'  => $reason,
        ]);
    }

    public function markError(string $message): void
    {
        $this->update([
            'status'               => 'error',
            'health_status'        => 'failed',
            'health_message'       => $message,
            'last_health_check_at' => now(),
        ]);
    }

    public function supportsCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? [], true);
    }
}
