<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'phone',
        'telegram',
        'instagram',
        'show_email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'show_email' => 'boolean',
        ];
    }

    // --- PROFILE ---

    /**
     * Public URL of the uploaded avatar, or null when the user has none.
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : null;
    }

    /**
     * Up to two initials, used when there is no avatar image.
     */
    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /**
     * Contact methods the user has chosen to share.
     *
     * @return list<array{label: string, value: string, url: string|null}>
     */
    public function contactMethods(): array
    {
        $methods = [];

        if ($this->show_email) {
            $methods[] = ['label' => 'Email', 'value' => $this->email, 'url' => 'mailto:'.$this->email];
        }

        if ($this->phone) {
            $methods[] = ['label' => 'Phone', 'value' => $this->phone, 'url' => 'tel:'.preg_replace('/[^\d+]/', '', $this->phone)];
        }

        if ($this->telegram) {
            $methods[] = ['label' => 'Telegram', 'value' => '@'.$this->telegram, 'url' => 'https://t.me/'.$this->telegram];
        }

        if ($this->instagram) {
            $methods[] = ['label' => 'Instagram', 'value' => '@'.$this->instagram, 'url' => 'https://instagram.com/'.$this->instagram];
        }

        return $methods;
    }

    /**
     * Contact details are only shared between partners of an accepted (or completed) swap.
     */
    public function canSeeContactOf(User $other): bool
    {
        if ($this->is($other)) {
            return true;
        }

        return SwapRequest::query()
            ->whereIn('status', ['accepted', 'completed'])
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q->where('sender_id', $this->id)->where('receiver_id', $other->id))
                ->orWhere(fn (Builder $q) => $q->where('sender_id', $other->id)->where('receiver_id', $this->id)))
            ->exists();
    }

    // --- SKILL RELATIONSHIPS ---

    /**
     * All skills associated with the user (both offered and wanted).
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->withPivot('type', 'proficiency')
            ->withTimestamps();
    }

    /**
     * Skills the user can teach. withPivotValue both filters on and writes type = 'offered'.
     */
    public function offeredSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->withPivotValue('type', 'offered')
            ->withPivot('proficiency')
            ->withTimestamps();
    }

    /**
     * Skills the user wants to learn. withPivotValue both filters on and writes type = 'wanted'.
     */
    public function wantedSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->withPivotValue('type', 'wanted')
            ->withPivot('proficiency')
            ->withTimestamps();
    }

    // --- SWAP REQUEST RELATIONSHIPS ---

    /**
     * Requests sent by this user to others.
     */
    public function sentSwapRequests(): HasMany
    {
        return $this->hasMany(SwapRequest::class, 'sender_id');
    }

    /**
     * Requests received by this user from others.
     */
    public function receivedSwapRequests(): HasMany
    {
        return $this->hasMany(SwapRequest::class, 'receiver_id');
    }

    /**
     * Users who teach $skillId and want to learn $inReturnSkillId.
     *
     * @param  Builder<User>  $query
     */
    public function scopeMatching(Builder $query, int $skillId, int $inReturnSkillId): void
    {
        $query
            ->whereHas('offeredSkills', fn (Builder $q) => $q->where('skills.id', $skillId))
            ->whereHas('wantedSkills', fn (Builder $q) => $q->where('skills.id', $inReturnSkillId));
    }
}
