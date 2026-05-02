<?php

declare(strict_types=1);

namespace Modules\Exception\Services\Contracts;

interface ExceptionService
{
    public function report(\Throwable $exception): void;

    public function render(\Throwable $exception): ?string;

    public function shouldReport(\Throwable $exception): bool;
}
