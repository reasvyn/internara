<?php

declare(strict_types=1);

namespace Modules\Admin\Services\Contracts;

interface AdminService
{
    public function getSystemStats(): array;

    public function manageUsers(array $filters): array;

    public function auditLogs(array $filters): array;
}
