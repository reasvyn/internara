<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Exceptions\Concerns\HasExceptionContext;
use RuntimeException;

abstract class AppException extends RuntimeException
{
    use HasExceptionContext;

    abstract public function statusCode(): int;

    public function isUserFacing(): bool
    {
        return true;
    }

    public function shouldReport(): bool
    {
        return true;
    }
}
