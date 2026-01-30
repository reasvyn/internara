<?php

declare(strict_types=1);

namespace Modules\Shared\Services\Contracts;

/**
 * Interface FormatterService
 *
 * Defines methods for normalizing and formatting various domain strings like paths and namespaces.
 */
interface FormatterService
{
    /**
     * Normalize and join path segments.
     */
    public function path(?string ...$paths): string;

    /**
     * Normalize and join namespace segments.
     */
    public function namespace(?string ...$parts): string;
}
