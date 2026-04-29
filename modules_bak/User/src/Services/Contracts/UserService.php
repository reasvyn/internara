<?php

declare(strict_types=1);

namespace Modules\User\Services\Contracts;

use Modules\User\Models\User;

interface UserService
{
    public function findById(string $id): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): void;
    public function delete(User $user): void;
    public function findByEmail(string $email): ?User;
    public function paginate(int $perPage = 15): \Illuminate\Pagination\Paginator;
}
