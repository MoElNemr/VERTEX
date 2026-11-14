<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookAccount extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'page_id',
        'page_name',
        'meta_user_id',
        'page_access_token',
        'token_type',
        'token_expires_at',
        'avatar_url',
        'granted_scopes',
        'status',
        'comment_reply_enabled',
        'comment_reply_text',
        'dm_message_text',
        'comment_keywords',
    ];

    protected $hidden = [
        'page_access_token',
    ];

    protected $casts = [
        'page_access_token' => 'encrypted',
        'comment_reply_enabled' => 'boolean',
        'comment_keywords' => 'array',
        'granted_scopes' => 'array',
        'token_expires_at' => 'datetime',
    ];
}
