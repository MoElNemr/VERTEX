<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAiSetting extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'ai_enabled',
        'ai_model',
        'ai_personality',
        'ai_instructions',
        'working_hours_only',
        'work_start_hour',
        'work_end_hour',
        'is_enabled',
        'provider',
        'api_key',
        'model',
        'system_prompt',
        'auto_reply_platforms',
        'reply_delay_seconds',
    ];

    protected $attributes = [
        'provider' => 'groq',
        'ai_model' => 'llama-3.3-70b-versatile',
        'model' => 'llama-3.3-70b-versatile',
    ];

    protected $casts = [
        'ai_enabled' => 'boolean',
        'working_hours_only' => 'boolean',
        'work_start_hour' => 'integer',
        'work_end_hour' => 'integer',
        'is_enabled' => 'boolean',
        'auto_reply_platforms' => 'array',
        'reply_delay_seconds' => 'integer',
        'api_key' => 'encrypted',
    ];

    public function getEffectiveApiKeyAttribute(): ?string
    {
        return $this->api_key ?: null;
    }

    public function getHasApiKeyAttribute(): bool
    {
        return !empty($this->api_key);
    }
}
