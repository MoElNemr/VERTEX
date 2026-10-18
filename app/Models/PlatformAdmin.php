<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlatformAdmin extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'mfa_enabled',
        'mfa_secret',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'mfa_enabled' => 'boolean',
        ];
    }

    /** Platform roles in ascending privilege order. */
    public const ROLES = ['support', 'finance', 'operations', 'owner'];

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function hasRole(string $role): bool
    {
        return in_array($this->role, array_slice(self::ROLES, array_search($role, self::ROLES) ?: 0), true);
    }

    public function can(string $ability, mixed $arguments = []): bool
    {
        // Owner can do everything
        if ($this->isOwner()) return true;

        return match ($ability) {
            'view-tenants', 'view-billing', 'view-channels', 'view-campaigns' => true,
            'adjust-wallet', 'manage-plans', 'manage-pricing' => in_array($this->role, ['finance', 'operations']),
            'manage-admins', 'manage-settings', 'manage-feature-flags' => $this->role === 'operations',
            default => false,
        };
    }
}
