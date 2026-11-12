<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SwapRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'offered_skill_id',
        'requested_skill_id',
        'status',
        'initial_message',
    ];

    /**
     * The user who initiated the request.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The user receiving the request.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * The skill the sender is offering to teach.
     */
    public function offeredSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'offered_skill_id');
    }

    /**
     * The skill the sender wants to learn in return.
     */
    public function requestedSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'requested_skill_id');
    }

    /**
     * Chat messages tied to this specific swap agreement.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function involves(User $user): bool
    {
        return (int) $this->sender_id === $user->id || (int) $this->receiver_id === $user->id;
    }

    /**
     * The participant who is not $user.
     */
    public function otherParty(User $user): ?User
    {
        return (int) $this->sender_id === $user->id ? $this->receiver : $this->sender;
    }
}
