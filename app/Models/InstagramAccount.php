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
    ];

    protected $hidden = [
        'access_token',
    ];
}
