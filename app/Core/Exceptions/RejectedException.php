<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class RejectedException extends ModuleException
{
    public function statusCode(): int
    {
        return 400;
    }
}
