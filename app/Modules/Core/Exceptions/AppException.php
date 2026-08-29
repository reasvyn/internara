<?php

declare(strict_types=1);

namespace App\Modules\Core\Exceptions;

use App\Modules\Core\Exceptions\Concerns\HasExceptionContext;
use RuntimeException;

abstract class AppException extends RuntimeException
{
    use HasExceptionContext;

    abstract public function statusCode(): int;
}
