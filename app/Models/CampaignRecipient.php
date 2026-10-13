<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'platform',
        'recipient_id',
        'recipient_name',
        'message_body',
        'custom_data',
        'status',
        'error_message',
        'platform_message_id',
        'sent_at',
    ];

    protected $casts = [
        'custom_data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
