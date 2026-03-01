<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Standardized Data Transfer Object for inter-module communication.
 *
 * Encapsulates operation success, data payloads, and localized message keys.
 */
final readonly class Result
{
    /**
     * @param bool $success Whether the operation was successful.
     * @param mixed|null $data The optional payload returned by the operation.
     * @param string|null $message An optional localized message key.
     * @param array $meta Additional metadata for the result.
     */
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $message = null,
        public array $meta = [],
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
    ): self {
        return new self(true, $data, $message, $meta);
    }

    /**
     * Create a failed result.
     */
    public static function failure(
        ?string $message = null,
        mixed $data = null,
        array $meta = [],
    ): self {
        return new self(false, $data, $message, $meta);
    }
}
