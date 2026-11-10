<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
    ];

    // Users linked to this skill (via the user_skills pivot)
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills')
            ->withPivot('type', 'proficiency')
            ->withTimestamps();
    }
}
