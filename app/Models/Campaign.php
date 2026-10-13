<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'type',
        'platforms',
        'message',
        'platform_messages',
        'status',
        'interval_seconds',
        'jitter_seconds',
        'total_recipients',
        'sent_count',
        'failed_count',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'platforms' => 'array',
        'platform_messages' => 'array',
        'metadata' => 'array',
        'interval_seconds' => 'integer',
        'jitter_seconds' => 'integer',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function pendingRecipients()
    {
        return $this->hasMany(CampaignRecipient::class)->where('status', 'pending');
    }

    public function sentRecipients()
    {
        return $this->hasMany(CampaignRecipient::class)->where('status', 'sent');
    }

    public function failedRecipients()
    {
        return $this->hasMany(CampaignRecipient::class)->where('status', 'failed');
    }
}
