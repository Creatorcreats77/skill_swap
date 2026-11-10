<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // --- SKILL RELATIONSHIPS ---

    // All skills associated with the user (both offered and wanted)
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
                    ->withPivot('type', 'proficiency')
                    ->withTimestamps();
    }

    // Skills the user can teach (type = 'offered')
    public function offeredSkills()
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
                    ->wherePivot('type', 'offered')
                    ->withPivot('proficiency')
                    ->withTimestamps();
    }

    // Skills the user wants to learn (type = 'wanted')
    public function wantedSkills()
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
                    ->wherePivot('type', 'wanted')
                    ->withPivot('proficiency')
                    ->withTimestamps();
    }

    // --- SWAP REQUEST RELATIONSHIPS ---

    // Requests sent by this user to others
    public function sentSwapRequests()
    {
        return $this->hasMany(SwapRequest::class, 'sender_id');
    }

    // Requests received by this user from others
    public function receivedSwapRequests()
    {
        return $this->hasMany(SwapRequest::class, 'receiver_id');
    }
}