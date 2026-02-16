<?php

declare(strict_types=1);

namespace Modules\Report\Services\Contracts;

/**
 * Defines the contract for orchestrating official document generation.
 *
 * This service manages the transition from domain-specific data providers
 * into authoritative institutional records (PDF/Excel), ensuring that
 * large-scale exports are handled asynchronously to preserve system
 * responsiveness.
 */
interface ReportGenerator
{
    /**
     * Dispatches an asynchronous generation request for a specific report.
     *
     * Orchestrates background workers to synthesize the document,
     * satisfying the institutional mandate for high-volume data delivery
     * without blocking the presentation layer.
     */
    public function queue(
        string $providerIdentifier,
        array $filters = [],
        ?string $userId = null,
    ): string;

    /**
     * Executes a synchronous document synthesis for immediate delivery.
     *
     * Note: Use only for low-complexity reports. High-volume data sets
     * MUST utilize the `queue()` method to ensure systemic stability.
     */
    public function generate(
        string $providerIdentifier,
        array $filters = [],
        ?string $userId = null,
    ): string;

    /**
     * Retrieves the registry of authorized institutional data providers.
     *
     * @return \Illuminate\Support\Collection<string, \Modules\Shared\Contracts\ExportableDataProvider>
     */
    public function getProviders(): \Illuminate\Support\Collection;
}
