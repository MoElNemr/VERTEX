<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'logo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teamMembers()
    {
        return $this->belongsToMany(TeamMember::class, 'business_team_member');
    }

    public function whatsappAccount()
    {
        return $this->hasOne(WhatsappAccount::class);
    }

    public function facebookAccount()
    {
        return $this->hasOne(FacebookAccount::class);
    }

    public function instagramAccount()
    {
        return $this->hasOne(InstagramAccount::class);
    }

    public function telegramAccount()
    {
        return $this->hasOne(TelegramAccount::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function quickReplies()
    {
        return $this->hasMany(QuickReply::class);
    }

    public function aiSetting()
    {
        return $this->hasOne(BusinessAiSetting::class);
    }
}
