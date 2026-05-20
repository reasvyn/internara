<?php

declare(strict_types=1);

namespace App\Domain\Core\Exceptions;

abstract class PresentationException extends AppException
{
    /**
     * Presentation exceptions occur at the UI/routing level.
     * Often related to missing parameters or invalid states not caught by validation.
     */
    public function isUserFacing(): bool
    {
        return true;
    }
}
