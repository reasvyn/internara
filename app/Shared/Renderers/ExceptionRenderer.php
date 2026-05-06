<?php

declare(strict_types=1);

namespace App\Shared\Renderers;

use App\Domain\Shared\Exceptions\DomainException;

final class ExceptionRenderer
{
    public function toCliOutput(DomainException $exception): string
    {
        return $exception->toCliOutput();
    }

    public function toLivewireFlash(DomainException $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'hint' => $exception->getHint(),
            'type' => 'error',
        ];
    }
}
