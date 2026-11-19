<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sender_id
 * @property int $recipient_id
 * @property string $body
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $sender
 * @property-read User $recipient
 */
#[Fillable(['sender_id', 'recipient_id', 'body', 'read_at'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * The user who sent the message.
     *
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The user who received the message.
     *
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Limit the query to the conversation between two users.
     *
     * @param  Builder<Message>  $query
     */
    public function scopeBetween(Builder $query, User $first, User $second): void
    {
        $query->where(function (Builder $query) use ($first, $second): void {
            $query->where(function (Builder $query) use ($first, $second): void {
                $query->where('sender_id', $first->id)
                    ->where('recipient_id', $second->id);
            })->orWhere(function (Builder $query) use ($first, $second): void {
                $query->where('sender_id', $second->id)
                    ->where('recipient_id', $first->id);
            });
        });
    }

    /**
     * Limit the query to messages sent to or by a user.
     *
     * @param  Builder<Message>  $query
     */
    public function scopeInvolving(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->where('sender_id', $user->id)
                ->orWhere('recipient_id', $user->id);
        });
    }

    /**
     * Whether the message has been read by its recipient.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
