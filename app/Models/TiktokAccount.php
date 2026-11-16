<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiktokAccount extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'open_id',
        'union_id',
        'username',
        'display_name',
        'avatar_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'refresh_expires_at',
        'granted_scopes',
        'status',
        'comment_reply_enabled',
        'comment_reply_text',
        'dm_message_text',
        'comment_keywords',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'comment_reply_enabled' => 'boolean',
        'comment_keywords' => 'array',
        'granted_scopes' => 'array',
        'token_expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime',
    ];
}
