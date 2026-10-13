<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookAccount extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'page_id',
        'page_name',
        'page_access_token',
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
        'comment_reply_enabled' => 'boolean',
        'comment_keywords' => 'array',
    ];
}
