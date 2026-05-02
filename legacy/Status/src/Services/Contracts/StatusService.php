<?php

declare(strict_types=1);

namespace Modules\Status\Services\Contracts;

interface StatusService
{
    public function getStatusesForModel(string $modelType): array;

    public function setStatus(string $modelType, string $modelId, string $status): void;

    public function getLatestStatus(string $modelType, string $modelId): ?object;
}
