<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'facebook_id',
        'avatar',
        'password',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trial'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latestOfMany();
    }

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function bulkWallet()
    {
        return $this->hasOne(BulkMessageWallet::class);
    }

    public function bulkTransactions()
    {
        return $this->hasMany(BulkMessageTransaction::class);
    }

    public function getOrCreateWallet(): BulkMessageWallet
    {
        return $this->bulkWallet()->firstOrCreate(
            ['user_id' => $this->id],
            ['balance' => 0.00, 'credits' => 0, 'currency' => 'EGP']
        );
    }

    public function canCreateBusiness(): bool
    {
        $activeSub = $this->activeSubscription;

        // إذا كان المستخدم جديد بدون اشتراك، نعتبر له صلاحية إنشاء بيزنس تجريبي
        if (! $activeSub || ! $activeSub->plan) {
            return $this->businesses()->count() < 1; // بيزنس تجريبي واحد مجاناً
        }

        $max = $activeSub->plan->max_businesses;
        if (is_null($max)) {
            return true; // unlimited
        }

        return $this->businesses()->count() < $max;
    }
}
