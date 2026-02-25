<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Modules\Admin\Services\Contracts\AdminService as Contract;
use Modules\Shared\Services\BaseService;
use Modules\User\Services\Contracts\UserService;

/**
 * Orchestrator for Administrative users.
 *
 * This service manages administrators by delegating core user operations to the UserService
 * and enforcing the 'admin' role context.
 */
class AdminService extends BaseService implements Contract
{
    public function __construct(protected UserService $userService) {}

    /**
     * {@inheritdoc}
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15,
    ): \Illuminate\Pagination\LengthAwarePaginator {
        // Enforce the 'admin' role filter
        $filters['role'] = 'admin';

        return $this->userService->paginate($filters, $perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $id): ?array
    {
        $user = $this->userService->find($id);

        if (! $user || ! $user->hasRole('admin')) {
            return null;
        }

        return $user->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): array
    {
        $data['roles'] = ['admin'];

        $user = $this->userService->create($data);

        return $user->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): array
    {
        $admin = $this->find($id);

        if (! $admin) {
            throw new \Modules\Exception\RecordNotFoundException(
                replace: ['record' => 'Admin', 'id' => $id],
            );
        }

        $user = $this->userService->update($id, $data);

        return $user->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $id, bool $force = false): bool
    {
        $admin = $this->find($id);

        if (! $admin) {
            return false;
        }

        return $this->userService->delete($id, $force);
    }
}
