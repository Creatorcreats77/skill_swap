<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A private chat between exactly two users (stored with user_one_id < user_two_id).
 */
class Conversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * The existing conversation between two users, if any.
     */
    public static function findBetween(User $a, User $b): ?self
    {
        return static::where('user_one_id', min($a->id, $b->id))
            ->where('user_two_id', max($a->id, $b->id))
            ->first();
    }

    /**
     * The conversation between two users, created if it does not exist yet.
     */
    public static function between(User $a, User $b): self
    {
        return static::firstOrCreate([
            'user_one_id' => min($a->id, $b->id),
            'user_two_id' => max($a->id, $b->id),
        ]);
    }

    /**
     * @param  Builder<Conversation>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id));
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        // By time first: migrated request notes have older timestamps but newer ids.
        return $this->hasOne(Message::class)->ofMany(['created_at' => 'max', 'id' => 'max']);
    }

    public function otherParticipant(User $user): User
    {
        return (int) $this->user_one_id === $user->id ? $this->userTwo : $this->userOne;
    }

    /**
     * Add a message from $sender and bump the conversation to the top of the inbox.
     */
    public function send(User $sender, string $body, ?SwapRequest $swapRequest = null): Message
    {
        $message = $this->messages()->create([
            'sender_id' => $sender->id,
            'swap_request_id' => $swapRequest?->id,
            'body' => $body,
        ]);

        $this->update(['last_message_at' => $message->created_at]);

        return $message;
    }

    /**
     * Unread messages sent to $user across all of their conversations.
     */
    public static function unreadCountFor(User $user): int
    {
        return Message::query()
            ->where('is_read', false)
            ->where('sender_id', '!=', $user->id)
            ->whereIn('conversation_id', static::forUser($user)->select('id'))
            ->count();
    }
}
