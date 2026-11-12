<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
    ];

    /**
     * All users linked to this skill (via the user_skills pivot), regardless of type.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')
            ->withPivot('type', 'proficiency')
            ->withTimestamps();
    }

    /**
     * Users who can teach this skill.
     */
    public function offeredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')
            ->wherePivot('type', 'offered')
            ->withPivot('proficiency')
            ->withTimestamps();
    }

    /**
     * Users who want to learn this skill.
     */
    public function wantedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')
            ->wherePivot('type', 'wanted')
            ->withTimestamps();
    }

    /**
     * Build a slug for the given name that is not used by any other skill.
     */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'skill';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
