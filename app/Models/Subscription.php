<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'messages_limit',
        'messages_used',
        'quota_resets_at',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'messages_limit' => 'integer',
        'messages_used' => 'integer',
        'quota_resets_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Subscription $sub) {
            if ($sub->isDirty('plan_id') || (! $sub->exists && $sub->messages_limit === null)) {
                $plan = $sub->relationLoaded('plan') ? $sub->plan : Plan::find($sub->plan_id);
                if ($plan) {
                    $sub->messages_limit = $plan->monthly_message_quota;
                }
            }
            if (empty($sub->quota_resets_at)) {
                $sub->quota_resets_at = now()->addMonth();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']) &&
            (! $this->ends_at || $this->ends_at->isFuture());
    }
}
