<?php

declare(strict_types=1);

namespace App\Auth\SuperAdmin\Entities;

use App\Auth\Permissions\Enums\Role;
use App\Core\Entities\BaseEntity;
use App\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class SuperAdminIntegrityRules extends BaseEntity
{
    public function __construct(
        private string $name,
        private string $username,
        private bool $isSuperAdmin,
        private ?AccountStatus $status,
        private int $superAdminCount,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            name: $model->name,
            username: $model->username,
            isSuperAdmin: $model->hasRole(Role::SUPER_ADMIN->value),
            status: $model->status instanceof AccountStatus
                ? $model->status
                : ($model->status
                    ? AccountStatus::tryFrom((string) $model->status)
                    : null),
            superAdminCount: (int) ($model->super_admin_count ?? 0),
        );
    }

    public function isNameValid(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        return $this->name === config('setup.defaults.admin_name', 'Administrator');
    }

    public function isUsernameValid(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        return $this->username === config('setup.defaults.admin_username', 'superadmin');
    }

    public function isLastSuperAdmin(): bool
    {
        return $this->isSuperAdmin && $this->superAdminCount <= 1;
    }

    public function canBeDeleted(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        // Super admin can never be deleted
        return false;
    }

    public function canBeLocked(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        // Super admin can never be locked out
        return false;
    }

    public function canChangeName(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        // Super admin name is immutable
        return false;
    }

    public function canChangeUsername(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        // Super admin username is immutable
        return false;
    }

    public function hasProtectedStatus(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        return $this->status === AccountStatus::PROTECTED;
    }

    public function isImmutable(): bool
    {
        if (! $this->isSuperAdmin) {
            return false;
        }

        return $this->hasProtectedStatus();
    }

    /**
     * Check if the system has exactly one superadmin account.
     */
    public function isSingleSuperAdmin(): bool
    {
        return $this->isSuperAdmin && $this->superAdminCount === 1;
    }

    /**
     * Check if the system needs a superadmin account (none exist).
     */
    public function needsSuperAdmin(): bool
    {
        return ! $this->isSuperAdmin && $this->superAdminCount === 0;
    }
}
