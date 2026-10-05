<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramAccount extends Model
{
    use HasFactory, BelongsToBusiness;

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
}
