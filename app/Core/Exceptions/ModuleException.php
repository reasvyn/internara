<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Exceptions\Concerns\HasExceptionContext;
use RuntimeException;

abstract class ModuleException extends RuntimeException
{
    use HasExceptionContext;

    abstract public function statusCode(): int;
}
