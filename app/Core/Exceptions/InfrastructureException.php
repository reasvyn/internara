<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

abstract class InfrastructureException extends AppException
{
    public function statusCode(): int
    {
        return 500;
    }

    public function isUserFacing(): bool
    {
        return false;
    }
}
