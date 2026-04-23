<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Media\Concerns\InteractsWithMedia;
use Modules\Permission\Enums\Role;
use Modules\Profile\Models\Concerns\HasProfileRelation;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatuses;
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
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
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

        return ! is_null($this->email_verified_at);
    }

    /**
     * Only send the email verification notification when an email is present.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (! is_null($this->email)) {
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
        $isStatusVerified = $this->getStatus() === \Modules\Status\Enums\Status::VERIFIED;

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

    public function canTransitionTo(ModulesStatusnumsStatus $targetStatus): bool
    {
        $currentStatus = $this->getStatus();
        if (!$currentStatus) return false;
        return $currentStatus->canTransitionTo($targetStatus);
    }

    public function transitionTo(
        \Modules\Status\Enums\Status $newStatus,
        ?string $reason = null,
        ?string $triggeredById = null,
    ): SpatieModelStatusModelsStatus {
        $currentStatus = $this->getStatus();
        if ($currentStatus && !$currentStatus->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Cannot transition from {$currentStatus->value} to {$newStatus->value}");
        }
        $history = SpatieModelStatusModelsStatus::create([
            'user_id' => $this->id,
            'old_status' => $currentStatus?->value,
            'new_status' => $newStatus->value,
            'reason' => $reason,
            'triggered_by_user_id' => $triggeredById,
            'ip_address' => request()?->ip(),
        ]);
        $this->setStatus($newStatus->value);
        return $history;
    }

    public function isProtected(): bool
    {
        return $this->hasRole(\Modules\Permission\Enums\Role::SUPER_ADMIN->value) ||
               $this->getStatus() === \Modules\Status\Enums\Status::PROTECTED;
    }

    public function isAccountVerified(): bool
    {
        $status = $this->getStatus();
        return in_array($status, [\Modules\Status\Enums\Status::VERIFIED, \Modules\Status\Enums\Status::PROTECTED]);
    }

    public function isAccountRestricted(): bool { return $this->getStatus() === \Modules\Status\Enums\Status::RESTRICTED; }

    public function isAccountSuspended(): bool { return $this->getStatus() === \Modules\Status\Enums\Status::SUSPENDED; }

    public function isAccountArchived(): bool { return $this->getStatus() === \Modules\Status\Enums\Status::ARCHIVED; }

    public function isAccountInactive(): bool { return $this->getStatus() === \Modules\Status\Enums\Status::INACTIVE; }

    public function getActiveRestrictions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->restrictions()->where('is_active', true)->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->get();
    }

    public function isRestricted(string $restrictionKey): bool
    {
        return $this->getActiveRestrictions()->where('restriction_key', $restrictionKey)->isNotEmpty();
    }

    public function getLastStatusChangeAt(): ?\Illuminate\Support\Carbon
    {
        return $this->statusHistory()->latest('created_at')->first()?->created_at;
    }

    public function getLastActivityAt(): ?\Illuminate\Support\Carbon
    {
        return $this->activity()->where('event', 'login')->latest('created_at')->first()?->created_at;
    }

    public function isIdle(int $days = 180): bool
    {
        $lastActivity = $this->getLastActivityAt();
        return $lastActivity ? $lastActivity->addDays($days)->isPast() : $this->created_at->addDays($days)->isPast();
    }

    public function daysUntilAutoArchive(int $totalDays = 365): int
    {
        $lastActivity = $this->getLastActivityAt();
        $archiveDate = $lastActivity ? $lastActivity->addDays($totalDays) : $this->created_at->addDays($totalDays);
        return now()->diffInDays($archiveDate, absolute: false);
    }

    public function statusHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SpatieModelStatusModelsStatus::class, 'user_id');
    }

    public function restrictions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Status\Models\AccountRestriction::class, 'user_id');
    }

}
