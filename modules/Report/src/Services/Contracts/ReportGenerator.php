<?php

declare(strict_types=1);

namespace Modules\Report\Services\Contracts;

/**
 * Report Generator Contract
 */
interface ReportGenerator
{
    /**
     * Dispatch a background job to generate a report.
     */
    public function queue(string $providerIdentifier, array $filters = []): string;

    /**
     * Generate a report immediately (Synchronous).
     */
    public function generate(string $providerIdentifier, array $filters = []): string;
}
