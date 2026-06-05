<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Exceptions\Concerns\HasExceptionContext;
use RuntimeException;

/**
 * Module-level exception hierarchy, deliberately decoupled from AppException.
 *
 * ModuleException is NOT a child of AppException. This is a deliberate design
 * choice so that module catch blocks remain isolated from the layered framework.
 * The HasExceptionContext trait provides the same fluent API without inheritance.
 */
abstract class ModuleException extends RuntimeException
{
    use HasExceptionContext;
}
