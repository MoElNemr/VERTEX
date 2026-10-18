<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappAccount extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'session_id',
        'phone_number',
        'qr_code',
        'status',
        'last_connected_at',
    ];

    protected $casts = [
        'last_connected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $account) {
            \Illuminate\Support\Facades\Cache::forget("whatsapp_account_biz_{$account->business_id}");
            \Illuminate\Support\Facades\Cache::forget("whatsapp_account_session_{$account->session_id}");
        });

        static::deleted(function (self $account) {
            \Illuminate\Support\Facades\Cache::forget("whatsapp_account_biz_{$account->business_id}");
            \Illuminate\Support\Facades\Cache::forget("whatsapp_account_session_{$account->session_id}");
        });
    }
}
