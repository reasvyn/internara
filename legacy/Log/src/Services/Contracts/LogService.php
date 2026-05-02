<?php

declare(strict_types=1);

namespace Modules\Log\Services\Contracts;

interface LogService
{
    public function getActivityLogs(array $filters): array;

    public function clearOldLogs(int $daysToKeep = 90): int;

    public function exportLogs(array $filters): string;
}
