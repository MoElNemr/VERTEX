<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramAccount extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'instagram_business_id',
        'username',
        'access_token',
        'status',
        'comment_reply_enabled',
        'comment_reply_text',
        'dm_message_text',
        'comment_keywords',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'comment_reply_enabled' => 'boolean',
        'comment_keywords' => 'array',
    ];
}
