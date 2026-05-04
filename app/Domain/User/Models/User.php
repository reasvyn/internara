<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Core\Concerns\HasUuid;
use App\Domain\Document\Models\GeneratedReport;
use App\Domain\Guidance\Models\HandbookAcknowledgement;
use App\Domain\Internship\Models\Registration;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'username', 'password', 'setup_required', 'locked_at', 'locked_reason'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, HasStatuses, HasUuid, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_at' => 'datetime',
            'password' => 'hashed',
            'setup_required' => 'boolean',
        ];
    }

    /**
     * Get the profile associated with the user.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get all internship registrations for this user (as student, teacher, or mentor).
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'student_id');
    }

    /**
     * Get registrations where this user is the assigned teacher.
     */
    public function teachingRegistrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'teacher_id');
    }

    /**
     * Get registrations where this user is the assigned mentor.
     */
    public function mentoringRegistrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'mentor_id');
    }

    /**
     * Get all generated reports for this user.
     */
    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }

    /**
     * Get all handbook acknowledgements for this user.
     */
    public function handbookAcknowledgements(): HasMany
    {
        return $this->hasMany(HandbookAcknowledgement::class);
    }

    /**
     * Check if the user is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->latestStatus()?->name === AccountStatus::SUSPENDED->value;
    }

    /**
     * Check if the user is archived.
     */
    public function isArchived(): bool
    {
        return $this->latestStatus()?->name === AccountStatus::ARCHIVED->value;
    }

    /**
     * Check if the user is inactive.
     */
    public function isInactive(): bool
    {
        return $this->latestStatus()?->name === AccountStatus::INACTIVE->value;
    }

    /**
     * Check if the user requires account setup.
     */
    public function requiresSetup(): bool
    {
        return (bool) $this->setup_required;
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function lock(string $reason = 'too_many_failed_attempts'): void
    {
        $this->update([
            'locked_at' => now(),
            'locked_reason' => $reason,
        ]);
    }

    public function unlock(): void
    {
        $this->update([
            'locked_at' => null,
            'locked_reason' => null,
        ]);
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->whereNotNull('locked_at');
    }

    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->whereNull('locked_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->unlocked()->where('setup_required', false);
    }

    public function scopeRoleType(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', fn ($q) => $q->where('name', $role));
    }
}
