<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Admin\Services\Contracts\AdminService as Contract;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Orchestrator for Administrative users.
 */
class AdminService extends EloquentQuery implements Contract
{
    public function __construct(
        User $model,
        protected UserService $userService
    ) {
        $this->setModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $filters = [], array $columns = ['*']): Builder
    {
        if (! $this->baseQuery) {
            $this->setBaseQuery($this->model->role('admin'));
        }

        return parent::query($filters, $columns);
    }

    /**
     * Create a new Admin account.
     */
    public function create(array $data): User
    {
        $data['roles'] = ['admin'];

        return $this->userService->create($data);
    }

    /**
     * Update an Admin account.
     */
    public function update(mixed $id, array $data): User
    {
        $user = $this->find($id);

        if (! $user || ! $user->hasRole('admin')) {
            throw new \Modules\Exception\RecordNotFoundException(
                replace: ['record' => 'Admin', 'id' => $id]
            );
        }

        return $this->userService->update($id, $data);
    }

    /**
     * Delete an Admin account.
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        $user = $this->find($id);

        if (! $user || ! $user->hasRole('admin')) {
            return false;
        }

        return $this->userService->delete($id, $force);
    }
}
