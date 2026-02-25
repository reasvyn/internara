<?php

declare(strict_types=1);

namespace Modules\Setup\Onboarding\Services\Contracts;

/**
 * Interface OnboardingService
 *
 * Defines the contract for batch onboarding operations.
 */
interface OnboardingService
{
    /**
     * Import stakeholders from a CSV file.
     *
     * @param string $filePath Absolute path to the CSV file.
     * @param string $type The stakeholder type (student, teacher, mentor).
     *
     * @return array{success: int, failure: int, errors: array}
     */
    public function importFromCsv(string $filePath, string $type): array;

    /**
     * Get the CSV template content for a specific stakeholder type.
     */
    public function getTemplate(string $type): string;
}
