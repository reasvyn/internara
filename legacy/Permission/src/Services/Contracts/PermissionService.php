<?php

declare(strict_types=1);

namespace Modules\Permission\Services\Contracts;

use Modules\Permission\Enums\Permission;

interface PermissionService
{
    public function findById(string $id): ?object;

    public function getRoles(): array;

    public function assignRole(string $userId, string $role): void;

    public function hasPermission(string $userId, Permission $permission): bool;

    public function getDropdownOptions(): array;
}
