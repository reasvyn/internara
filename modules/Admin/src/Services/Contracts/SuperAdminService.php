<?php

declare(strict_types=1);

namespace Modules\Admin\Services\Contracts;

use Modules\User\Models\User;

/**
 * Interface SuperAdminService
 *
 * Defines the contract for managing the system-wide SuperAdmin account.
 */
interface SuperAdminService
{
    /**
     * Get the single SuperAdmin user instance.
     */
    public function getSuperAdmin(): ?User;

    /**
     * Create or register the initial SuperAdmin during setup.
     */
    public function create(array $data): User;

    /**
     * Update the existing SuperAdmin account.
     */
    public function update(string $id, array $data): User;

    /**
     * Atomically save or update the SuperAdmin account.
     */
    public function save(array $attributes, array $values = []): User;
}
