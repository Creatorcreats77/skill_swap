<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // The user who initiated the request
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // The user receiving the request
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // The skill the sender is offering to teach
    public function offeredSkill()
    {
        return $this->belongsTo(Skill::class, 'offered_skill_id');
    }

    // The skill the sender wants to learn in return
    public function requestedSkill()
    {
        return $this->belongsTo(Skill::class, 'requested_skill_id');
    }

    // Chat messages tied to this specific swap agreement
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}