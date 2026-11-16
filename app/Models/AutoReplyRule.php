<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoReplyRule extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'platform',
        'trigger_type',
        'match_type',
        'keywords',
        'reply_text',
        'private_dm_text',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];
}
