<?php

declare(strict_types=1);

namespace Modules\Shared\Services\Contracts;

/**
 * Interface MaskerService
 *
 * Defines methods for masking sensitive information (PII) like emails or phone numbers.
 */
interface MaskerService
{
    /**
     * Mask an email address.
     */
    public function email(?string $email): string;

    /**
     * Mask a sensitive string.
     */
    public function sensitive(string $value, int $keepStart = 3, int $keepEnd = 2): string;
}
