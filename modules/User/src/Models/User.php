<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Media\Concerns\InteractsWithMedia;
use Modules\Permission\Enums\Role;
use Modules\Profile\Models\Concerns\HasProfileRelation;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatuses;
use Modules\Status\Enums\Status;
use Modules\Status\Models\AccountRestriction;
use Modules\Status\Models\AccountStatusHistory;
use Modules\User\Database\Factories\UserFactory;
use Modules\User\Support\UsernameGenerator;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * Represents a user in the system.
 */
class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use HasFactory;
    use HasProfileRelation;
    use HasRoles;
    use HasStatuses;
    use HasUuid;
    use InteractsWithActivityLog;
    use InteractsWithMedia;
    use MustVerifyEmailTrait;
    use Notifiable;

    /**
     * The name of the activity log for this model.
     */
    protected string $activityLogName = 'profile';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'username',
        'password',
        'setup_required',
        'onboarding_batch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->username)) {
                $user->username = UsernameGenerator::generate();
            }
        });
    }

    /**
     * Determine if the model should use UUIDs.
     */
    protected function usesUuid(): bool
    {
        return true;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'setup_required' => 'boolean',
        ];
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn(string $word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // ─── Email-optional overrides ────────────────────────────────────────────────

    /**
     * Users without an email address are considered email-verified.
     * Email is optional in this system; username is the primary identity.
     */
    public function hasVerifiedEmail(): bool
    {
        if (is_null($this->email)) {
            return true;
        }

        return !is_null($this->email_verified_at);
    }

    /**
     * Only send the email verification notification when an email is present.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (!is_null($this->email)) {
            parent::sendEmailVerificationNotification();
        }
    }

    // ─── Relations ───────────────────────────────────────────────────────────────

    public function accountTokens(): HasMany
    {
        return $this->hasMany(AccountToken::class);
    }

    // ─── Status helpers ──────────────────────────────────────────────────────────

    /**
     * Check if the user is verified (email verified AND status is verified).
     */
    public function verified(): bool
    {
        $isEmailVerified = $this->hasVerifiedEmail();
        $isStatusVerified = $this->getStatus() === Status::VERIFIED;

        return $isEmailVerified && $isStatusVerified;
    }

    /**
     * Whether this account still has pending setup steps.
     * True from the moment a provisioning token is issued until the user
     * successfully claims the account (or admin explicitly clears the flag).
     */
    public function requiresSetup(): bool
    {
        return (bool) $this->setup_required;
    }

    /**
     * Whether this account has ever had its activation code claimed.
     * An account is "claimed" when at least one activation token has been consumed.
     */
    public function hasBeenClaimed(): bool
    {
        return $this->accountTokens()
            ->where('type', AccountToken::TYPE_ACTIVATION)
            ->whereNotNull('claimed_at')
            ->exists();
    }

    /**
     * Register the media collections for the user's avatar.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_AVATAR)->singleFile();
    }

    /**
     * The URL of the user's avatar.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::COLLECTION_AVATAR);
    }

    /**
     * Set the user's avatar.
     */
    public function changeAvatar(
        string|UploadedFile $file,
        string $collectionName = self::COLLECTION_AVATAR,
    ): bool {
        return $this->setMedia($file, $collectionName);
    }

    /**
     * Scope a query to only include users with the 'super-admin' role.
     */
    public function scopeSuperAdmin(Builder $query): Builder
    {
        return $query->role(Role::SUPER_ADMIN->value);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    // ─── Account Lifecycle Management ────────────────────────────────────────

    /**
     * Transition the user to a new status with a detailed audit trail.
     *
     * @param Status $newStatus The target status.
     * @param string|null $reason The reason for the transition.
     * @param string|null $triggeredById The ID of the user (admin) who triggered the change.
     *
     * @throws \InvalidArgumentException If the transition is invalid.
     */
    public function transitionTo(
        Status $newStatus,
        ?string $reason = null,
        ?string $triggeredById = null,
    ): AccountStatusHistory {
        $currentStatus = $this->getStatus();

        if ($currentStatus && !$currentStatus->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$currentStatus->value} to {$newStatus->value}",
            );
        }

        // 1. Create a detailed audit log entry in the specialized history table (S1 - Secure)
        $history = AccountStatusHistory::create([
            'user_id' => $this->id,
            'old_status' => $currentStatus?->value,
            'new_status' => $newStatus->value,
            'reason' => $reason,
            'triggered_by_user_id' => $triggeredById,
            'triggered_by_role' => $triggeredById ? self::find($triggeredById)?->role : null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);

        // 2. Update the actual model status via the underlying package
        $this->setStatus($newStatus->value, $reason);

        return $history;
    }

    /**
     * Determine if any active account-level restrictions exist for this user.
     */
    public function getActiveRestrictions(): Collection
    {
        return $this->restrictions()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Check if a specific restriction key is currently active for this user.
     */
    public function hasRestriction(string $restrictionKey): bool
    {
        return $this->getActiveRestrictions()
            ->where('restriction_key', $restrictionKey)
            ->isNotEmpty();
    }

    /**
     * Get the timestamp of the last login activity.
     */
    public function getLastActivityAt(): ?Carbon
    {
        return $this->activity()->where('event', 'login')->latest('created_at')->first()
            ?->created_at;
    }

    /**
     * Check if the user has been idle for the specified number of days.
     */
    public function isIdle(int $days = 180): bool
    {
        $lastActivity = $this->getLastActivityAt();

        return $lastActivity
            ? $lastActivity->addDays($days)->isPast()
            : $this->created_at->addDays($days)->isPast();
    }

    /**
     * Calculate the number of days remaining until the account is automatically archived.
     */
    public function daysUntilAutoArchive(int $totalDays = 365): int
    {
        $lastActivity = $this->getLastActivityAt();
        $archiveDate = $lastActivity
            ? $lastActivity->addDays($totalDays)
            : $this->created_at->addDays($totalDays);

        return (int) now()->diffInDays($archiveDate, absolute: false);
    }

    /**
     * Get the full status history for the user.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(AccountStatusHistory::class, 'user_id');
    }

    /**
     * Get the active restrictions for the user.
     */
    public function restrictions(): HasMany
    {
        return $this->hasMany(AccountRestriction::class, 'user_id');
    }
}
