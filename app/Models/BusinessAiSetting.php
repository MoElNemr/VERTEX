<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAiSetting extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'is_enabled',
        'provider',
        'api_key',
        'model',
        'system_prompt',
        'auto_reply_platforms',
        'reply_delay_seconds',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'auto_reply_platforms' => 'array',
        'reply_delay_seconds' => 'integer',
        'api_key' => 'encrypted',
    ];
}
