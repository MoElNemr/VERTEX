<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramAccount extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'instagram_business_id',
        'username',
        'meta_user_id',
        'linked_facebook_page_id',
        'access_token',
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
        'access_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'comment_reply_enabled' => 'boolean',
        'comment_keywords' => 'array',
        'granted_scopes' => 'array',
        'token_expires_at' => 'datetime',
    ];
}
