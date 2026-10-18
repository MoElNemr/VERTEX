<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditEvent extends Model
{
    protected $fillable = [
        'actor_type',
        'actor_id',
        'business_id',
        'action',
        'target_type',
        'target_id',
        'before',
        'after',
        'reason',
        'ip_address',
        'request_id',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    /**
     * Log an audit event. Never throws — failures are silently logged.
     */
    public static function log(
        string $action,
        array $context = [],
        ?Request $request = null
    ): void {
        try {
            static::create([
                'actor_type' => $context['actor_type'] ?? null,
                'actor_id' => $context['actor_id'] ?? null,
                'business_id' => $context['business_id'] ?? null,
                'action' => $action,
                'target_type' => $context['target_type'] ?? null,
                'target_id' => $context['target_id'] ?? null,
                'before' => $context['before'] ?? null,
                'after' => $context['after'] ?? null,
                'reason' => $context['reason'] ?? null,
                'ip_address' => $request?->ip(),
                'request_id' => $request?->header('X-Request-ID') ?? substr(uniqid('req_', true), 0, 16),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AuditEvent::log failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
