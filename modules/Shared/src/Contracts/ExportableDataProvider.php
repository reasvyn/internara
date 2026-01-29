<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

/**
 * Exportable Data Provider Contract
 *
 * This contract defines how domain modules should provide structured data
 * to the Reporting Engine for PDF or Excel generation.
 */
interface ExportableDataProvider
{
    /**
     * Get the unique identifier for this data provider.
     */
    public function getIdentifier(): string;

    /**
     * Get the human-readable label for this report type.
     */
    public function getLabel(): string;

    /**
     * Get the structured data for the report based on provided filters.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array;

    /**
     * Get the Blade template path for this report.
     */
    public function getTemplate(): string;

    /**
     * Define the validation rules for the filters.
     */
    public function getFilterRules(): array;
}
