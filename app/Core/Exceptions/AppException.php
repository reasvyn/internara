<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Exceptions\Concerns\HasExceptionContext;
use RuntimeException;

abstract class AppException extends RuntimeException
{
    use HasExceptionContext;

    /**
     * Determine if the exception message is safe to display to the user.
     * Overridden by specific layer exceptions (e.g., Infrastructure is usually false).
     */
    public function isUserFacing(): bool
    {
        return true;
    }

    /**
     * Determine if this exception should be reported/logged.
     */
    public function shouldReport(): bool
    {
        return true;
    }
}
