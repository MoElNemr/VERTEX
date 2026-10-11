<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'platform',
        'platform_sender_id',
        'name',
        'avatar',
        'phone',
        'email',
    ];

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
