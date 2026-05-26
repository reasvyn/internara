<?php

declare(strict_types=1);

namespace App\Domain\Auth\Entities;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Entities\BaseEntity;
use App\Domain\User\Models\User;
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
            status: $model->status,
            superAdminCount: User::role(Role::SUPER_ADMIN->value)->count(),
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

        return false;
    }

    public function canBeLocked(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        return false;
    }

    public function canChangeName(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

        return false;
    }

    public function canChangeUsername(): bool
    {
        if (! $this->isSuperAdmin) {
            return true;
        }

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

        return true;
    }
}
