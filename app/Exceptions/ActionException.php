<?php

declare(strict_types=1);

namespace App\Exceptions;

abstract class ActionException extends AppException
{
    /**
     * Action exceptions occur during the orchestration of domain logic.
     * They are typically safe for users to see.
     */
    public function isUserFacing(): bool
    {
        return true;
    }
}
