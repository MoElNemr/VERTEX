<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramAccount extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'bot_username',
        'bot_token',
        'webhook_secret',
        'status',
    ];

    protected $hidden = [
        'bot_token',
        'webhook_secret',
    ];

    protected $casts = [
        'bot_token' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];
}
