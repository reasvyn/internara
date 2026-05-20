<?php

declare(strict_types=1);

namespace App\Domain\Core\Exceptions;

use App\Domain\Core\Exceptions\Concerns\HasExceptionContext;
use RuntimeException;

/**
 * Domain-level exception hierarchy, deliberately decoupled from AppException.
 *
 * DomainException is NOT a child of AppException. This is a deliberate design
 * choice so that domain catch blocks remain isolated from the layered framework.
 * The HasExceptionContext trait provides the same fluent API without inheritance.
 */
abstract class DomainException extends RuntimeException
{
    use HasExceptionContext;
}
