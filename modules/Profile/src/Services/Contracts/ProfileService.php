<?php

declare(strict_types=1);

namespace Modules\Profile\Services\Contracts;

use Modules\Profile\Models\Profile;

interface ProfileService
{
    public function findById(string $id): ?Profile;
    public function create(array $data): Profile;
    public function update(Profile $profile, array $data): void;
    public function delete(Profile $profile): void;
    public function getByUserId(string $userId): ?Profile;
}
