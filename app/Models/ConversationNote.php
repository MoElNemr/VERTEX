<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConversationNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'business_id',
        'author_id',
        'author_type',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Resolve the author (polymorphic-style without Laravel morph overhead).
     */
    public function getAuthorAttribute(): ?Model
    {
        if ($this->author_type === 'team_member') {
            return TeamMember::find($this->author_id);
        }
        return User::find($this->author_id);
    }
}
