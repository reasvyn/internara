<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\User\Apprentice;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'username', 'password', 'setup_required', 'locked_at', 'locked_reason'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, HasStatuses, HasUuids, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_at' => 'datetime',
            'password' => 'hashed',
            'setup_required' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function mentees(): HasMany
    {
        return $this->hasMany(Mentee::class);
    }

    public function mentors(): HasMany
    {
        return $this->hasMany(Mentor::class);
    }

    public function registrations(): HasManyThrough
    {
        return $this->hasManyThrough(Registration::class, Mentee::class, 'user_id', 'mentee_id');
    }

    public function activeRegistration(): ?Registration
    {
        return $this->registrations()
            ->whereHas('statuses', fn ($q) => $q->where('name', 'active')->latest())
            ->latest()
            ->first();
    }

    public function handbookAcknowledgements(): HasMany
    {
        return $this->hasMany(HandbookAcknowledgement::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role', 'assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function mentoringTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('role', 'mentor');
    }

    public function menteeTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('role', 'mentee');
    }

    public function asApprentice(): Apprentice
    {
        return Apprentice::fromModel($this);
    }

    public function isSuspended(): bool
    {
        return $this->asApprentice()->isSuspended();
    }

    public function isArchived(): bool
    {
        return $this->asApprentice()->isArchived();
    }

    public function isInactive(): bool
    {
        return $this->asApprentice()->isInactive();
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
