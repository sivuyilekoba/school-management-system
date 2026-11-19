<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property AnnouncementAudience $audience
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $author
 */
#[Fillable(['title', 'body', 'audience', 'published_at', 'expires_at', 'created_by'])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * The user who wrote the announcement.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Limit the query to announcements that are visible to readers.
     *
     * @param  Builder<Announcement>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Limit the query to announcements that have not expired.
     *
     * @param  Builder<Announcement>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Limit the query to the announcements addressed to a reader.
     *
     * @param  Builder<Announcement>  $query
     */
    public function scopeAddressedTo(Builder $query, User $user): void
    {
        $query->whereIn('audience', $this->audiencesFor($user));
    }

    /**
     * Whether this announcement is addressed to the given reader.
     */
    public function isAddressedTo(User $user): bool
    {
        return in_array($this->audience, $this->audiencesFor($user), true);
    }

    /**
     * The audiences that apply to a reader.
     *
     * @return array<int, AnnouncementAudience>
     */
    private function audiencesFor(User $user): array
    {
        $audiences = [AnnouncementAudience::Everyone];

        if ($user->isStudent()) {
            $audiences[] = AnnouncementAudience::Students;
        }

        if ($user->isParent()) {
            $audiences[] = AnnouncementAudience::Families;
        }

        if ($user->isStaff()) {
            $audiences[] = AnnouncementAudience::Staff;
        }

        return $audiences;
    }

    /**
     * Whether the announcement can be read by everyone with view access.
     */
    public function isVisible(): bool
    {
        return $this->published_at !== null && ! $this->published_at->isFuture();
    }

    /**
     * Whether the announcement has passed its expiry date.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * The publication state of the announcement.
     */
    public function status(): string
    {
        return match (true) {
            $this->published_at === null => 'draft',
            $this->published_at->isFuture() => 'scheduled',
            $this->isExpired() => 'expired',
            default => 'published',
        };
    }
}
