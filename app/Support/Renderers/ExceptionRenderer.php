<?php

declare(strict_types=1);

namespace App\Support\Renderers;

use App\Exceptions\DomainException;

final class ExceptionRenderer
{
    public static function toCliOutput(DomainException $exception): string
    {
        return $exception->toCliOutput();
    }

    public static function toLivewireFlash(DomainException $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'hint' => $exception->getHint(),
            'type' => 'error',
        ];
    }
}
