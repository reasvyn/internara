<?php

declare(strict_types=1);

namespace Modules\Admin\Services\Contracts;

/**
 * Interface AdminService
 *
 * Defines the contract for managing administrative accounts.
 */
interface AdminService
{
    /**
     * Get a paginated list of administrators.
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15,
    ): \Illuminate\Pagination\LengthAwarePaginator;

    /**
     * Find an administrator by their identity.
     */
    public function find(string $id): ?array;

    /**
     * Create a new administrator account.
     */
    public function create(array $data): array;

    /**
     * Update an administrator account.
     */
    public function update(string $id, array $data): array;

    /**
     * Delete an administrator account.
     */
    public function delete(string $id, bool $force = false): bool;
}
