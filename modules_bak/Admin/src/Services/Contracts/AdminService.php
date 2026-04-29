<?php

declare(strict_types=1);

namespace Modules\Admin\Services\Contracts;

use Modules\User\Models\User;

interface AdminService
{
    public function getSystemStats(): array;
    public function manageUsers(array $filters): array;
    public function auditLogs(array $filters): array;
}
