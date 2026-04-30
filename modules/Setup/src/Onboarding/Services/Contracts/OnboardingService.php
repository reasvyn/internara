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
     * After successful rows, each user receives an activation code via
     * AccountProvisioningService. The plaintext codes are returned once
     * in the `credentials` key and never stored in plaintext.
     *
     * @param string $filePath Absolute path to the CSV file.
     * @param string $type The stakeholder type (student, teacher, mentor).
     * @param int $expiresInDays Days until activation codes expire (0 = no expiry).
     *
     * @return array{success: int, failure: int, errors: array, credentials: array<array{name: string, username: string, code: string}>}
     */
    public function importFromCsv(string $filePath, string $type, int $expiresInDays = 30): array;

    /**
     * Get the CSV template content for a specific stakeholder type.
     */
    public function getTemplate(string $type): string;
}
