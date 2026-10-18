<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'platform',
        'platform_sender_id',
        'name',
        'avatar',
        'phone',
        'email',
        'notes',
        'tags',
        'notification_token',
        'token_frequency',
        'token_expires_at',
        'optin_topic',
    ];

    protected $casts = [
        'tags' => 'array',
        'token_expires_at' => 'datetime',
    ];

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
