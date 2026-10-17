<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkMessageTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'payment_transaction_id',
        'type',
        'amount',
        'credits',
        'balance_after',
        'credits_after',
        'platform',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credits' => 'integer',
        'balance_after' => 'decimal:2',
        'credits_after' => 'integer',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class);
    }
}
