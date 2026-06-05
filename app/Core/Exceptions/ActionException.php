<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

abstract class ActionException extends AppException
{
    /**
     * Action exceptions occur during the orchestration of module logic.
     * They are typically safe for users to see.
     */
    public function isUserFacing(): bool
    {
        return true;
    }
}
