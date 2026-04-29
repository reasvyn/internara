<?php

declare(strict_types=1);

namespace Modules\Core\Services\Contracts;

interface CoreService
{
    public function getSystemInfo(): array;
    public function runHealthCheck(): array;
    public function getVersion(): string;
}
