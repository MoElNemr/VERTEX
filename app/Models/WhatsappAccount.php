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
}
