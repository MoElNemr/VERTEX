<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'max_businesses',
        'price_monthly',
        'price_yearly',
        'features',
        'is_active',
    ];

    protected $casts = [
        'max_businesses' => 'integer',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('active_pricing_plans');
            Cache::forget('all_pricing_plans');
        });

        static::deleted(function () {
            Cache::forget('active_pricing_plans');
            Cache::forget('all_pricing_plans');
        });
    }

    /**
     * Get active pricing plans with caching.
     */
    public static function getActivePlans()
    {
        return Cache::remember('active_pricing_plans', 3600, function () {
            return static::where('is_active', true)->get();
        });
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
