<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

abstract class ActionException extends AppException
{
    public function statusCode(): int
    {
        return 400;
    }

    public function isUserFacing(): bool
    {
        return true;
    }
}
