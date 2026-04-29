<?php

declare(strict_types=1);

namespace Modules\Admin\Services\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface SuperAdminService
 *
 * Defines the contract for managing the system-wide SuperAdmin account.
 */
interface SuperAdminService
{
    /**
     * Get the single SuperAdmin user instance.
     *
     * @return (Authenticatable&Model)|null
     */
    public function getSuperAdmin(): (Authenticatable&Model)|null;

    /**
     * Create or register the initial SuperAdmin during setup.
     */
    public function create(array $data): Authenticatable&Model;

    /**
     * Update the existing SuperAdmin account.
     */
    public function update(Model $model, array $data): void;

    /**
     * Atomically save or update the SuperAdmin account.
     */
    public function save(array $attributes, array $values = []): Authenticatable&Model;
}
