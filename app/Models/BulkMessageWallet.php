<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkMessageWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'credits',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'credits' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
