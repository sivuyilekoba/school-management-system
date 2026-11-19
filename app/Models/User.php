<?php

namespace App\Models;

use App\Enums\Role as RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Student|null $student
 * @property-read Family|null $family
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * The student record linked to this account.
     *
     * @return HasOne<Student, $this>
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * The family record linked to this account.
     *
     * @return HasOne<Family, $this>
     */
    public function family(): HasOne
    {
        return $this->hasOne(Family::class);
    }

    /**
     * The messages sent by this user.
     *
     * @return HasMany<Message, $this>
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * The messages received by this user.
     *
     * @return HasMany<Message, $this>
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    /**
     * Whether the account belongs to a student.
     */
    public function isStudent(): bool
    {
        return $this->hasRole(RoleName::Student->value);
    }

    /**
     * Whether the account belongs to a parent or guardian.
     */
    public function isParent(): bool
    {
        return $this->hasRole(RoleName::Parent->value);
    }

    /**
     * Whether the account uses the student and family portal.
     */
    public function isPortalUser(): bool
    {
        return $this->isStudent() || $this->isParent();
    }

    /**
     * Whether the account belongs to a member of staff.
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole([
            RoleName::Teacher->value,
            RoleName::Administrator->value,
            RoleName::SupportStaff->value,
        ]);
    }

    /**
     * Whether this user is allowed to start a conversation with another user.
     */
    public function canMessage(User $recipient): bool
    {
        if ($this->is($recipient)) {
            return false;
        }

        if ($this->isPortalUser()) {
            return $recipient->isStaff();
        }

        return $recipient->isStaff() || $recipient->isPortalUser();
    }
}
