<?php

declare(strict_types=1);

namespace App\User\Models;

use App\Auth\Account\Entities\AccountActivation;
use App\Auth\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\Core\Models\BaseAuthenticatable;
use App\Enrollment\Registration\Models\Registration;
use App\User\Entities\AdminEntity;
use App\User\Entities\Apprentice;
use App\User\Entities\StudentEntity;
use App\User\Entities\SupervisorEntity;
use App\User\Entities\TeacherEntity;
use App\User\Enums\AccountStatus;
use App\User\Observers\UserObserver;
use App\User\Profile\Models\Profile;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

#[
    Fillable([
        'name',
        'email',
        'username',
        'password',
        'setup_required',
        'locked_at',
        'locked_reason',
        'status',
        'is_active',
    ]),
]
#[Hidden(['password', 'remember_token'])]
class User extends BaseAuthenticatable implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, Notifiable;
    use HasRoles {
        hasRole as parentHasRole;
        hasAnyRole as parentHasAnyRole;
        hasAllRoles as parentHasAllRoles;
        assignRole as parentAssignRole;
        removeRole as parentRemoveRole;
        syncRoles as parentSyncRoles;
        scopeRole as parentScopeRole;
        scopeWithoutRole as parentScopeWithoutRole;
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        if (is_string($roles) && $roles === 'super_admin') {
            $roles = 'superadmin';
        } elseif (is_array($roles)) {
            $roles = array_map(fn ($r) => $r === 'super_admin' ? 'superadmin' : $r, $roles);
        }

        return $this->parentHasRole($roles, $guard);
    }

    public function hasAnyRole(...$roles): bool
    {
        $normalized = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $normalized = array_merge(
                    $normalized,
                    array_map(fn ($r) => $r === 'super_admin' ? 'superadmin' : $r, $role),
                );
            } else {
                $normalized[] = $role === 'super_admin' ? 'superadmin' : $role;
            }
        }

        return $this->parentHasAnyRole($normalized);
    }

    public function hasAllRoles(...$roles): bool
    {
        $normalized = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $normalized = array_merge(
                    $normalized,
                    array_map(fn ($r) => $r === 'super_admin' ? 'superadmin' : $r, $role),
                );
            } else {
                $normalized[] = $role === 'super_admin' ? 'superadmin' : $role;
            }
        }

        return $this->parentHasAllRoles($normalized);
    }

    public function assignRole(...$roles): static
    {
        $normalized = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $normalized = array_merge(
                    $normalized,
                    array_map(fn ($r) => $r === 'super_admin' ? 'superadmin' : $r, $role),
                );
            } else {
                $normalized[] = $role === 'super_admin' ? 'superadmin' : $role;
            }
        }

        return $this->parentAssignRole($normalized);
    }

    public function removeRole($role): static
    {
        if (is_string($role) && $role === 'super_admin') {
            $role = 'superadmin';
        }

        return $this->parentRemoveRole($role);
    }

    public function syncRoles(...$roles): static
    {
        $normalized = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $normalized = array_merge(
                    $normalized,
                    array_map(fn ($r) => $r === 'super_admin' ? 'superadmin' : $r, $role),
                );
            } else {
                $normalized[] = $role === 'super_admin' ? 'superadmin' : $role;
            }
        }

        return $this->parentSyncRoles($normalized);
    }

    public function scopeRole(Builder $query, $roles, $guard = null): Builder
    {
        if (is_string($roles) && $roles === 'super_admin') {
            $roles = 'superadmin';
        } elseif (is_array($roles)) {
            $roles = array_map(fn ($r) => $r === 'super_admin' ? 'superadmin' : $r, $roles);
        }

        return $this->parentScopeRole($query, $roles, $guard);
    }

    public function scopeWithoutRole(Builder $query, $roles, $guard = null): Builder
    {
        if (is_string($roles) && $roles === 'super_admin') {
            $roles = 'superadmin';
        } elseif (is_array($roles)) {
            $roles = array_map(fn ($r) => $r === 'super_admin' ? 'superadmin' : $r, $roles);
        }

        return $this->parentScopeWithoutRole($query, $roles, $guard);
    }

    public function delete(): ?bool
    {
        if ($this->hasRole('superadmin')) {
            throw new RuntimeException('Super administrator accounts cannot be deleted.');
        }

        return parent::delete();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_at' => 'datetime',
            'first_login_at' => 'datetime',
            'password' => 'hashed',
            'setup_required' => 'boolean',
            'status' => AccountStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function setStatus(string|AccountStatus $status, ?string $reason = null): static
    {
        $value = $status instanceof AccountStatus ? $status->value : $status;
        $this->forceFill(['status' => $value])->save();

        return $this;
    }

    public function latestStatus(): object
    {
        $value = $this->status instanceof AccountStatus ? $this->status->value : $this->status;

        return (object) ['name' => $value];
    }

    protected static function booted(): void
    {
        static::observe(UserObserver::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'student_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(200)->height(200)->format('webp')->nonQueued();
    }

    public function initials(): string
    {
        $words = explode(' ', trim($this->name));

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr(end($words), 0, 1));
        }

        return strtoupper(substr($this->name, 0, 2));
    }

    public function asApprentice(): Apprentice
    {
        return Apprentice::fromModel($this);
    }

    public function asStudent(): StudentEntity
    {
        return StudentEntity::fromModel($this);
    }

    public function asTeacher(): TeacherEntity
    {
        return TeacherEntity::fromModel($this);
    }

    public function asSupervisor(): SupervisorEntity
    {
        return SupervisorEntity::fromModel($this);
    }

    public function asAdmin(): AdminEntity
    {
        return AdminEntity::fromModel($this);
    }

    public function asAccountActivation(): AccountActivation
    {
        return AccountActivation::fromModel($this);
    }

    public function asSuperAdminIntegrityRules(): SuperAdminIntegrityRules
    {
        return SuperAdminIntegrityRules::fromModel($this);
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->whereNotNull('locked_at');
    }

    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->whereNull('locked_at');
    }

    public function getActiveRegistration(): ?Registration
    {
        return $this->registrations->first(fn (Registration $r) => $r->asRegistrationState()->isActive());
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
