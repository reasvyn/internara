<?php

declare(strict_types=1);

namespace App\Domain\Core\Exceptions;

abstract class InfrastructureException extends AppException
{
    /**
     * Infrastructure exceptions occur when external services fail (e.g. database, 3rd party APIs).
     * These should typically not be exposed to the end user in production to prevent data leakage.
     */
    public function isUserFacing(): bool
    {
        return false;
    }
}
