<?php

declare(strict_types=1);

namespace Modules\Setup\Services\Contracts;

/**
 * Interface SetupRequirementProvider
 * 
 * [S3 - Scalable] Allows external modules to provide validation logic for setup requirements.
 */
interface SetupRequirementProvider
{
    /**
     * The unique identifier for the record/requirement type (e.g., 'school', 'super-admin').
     */
    public function getRequirementIdentifier(): string;

    /**
     * Determines if the specific requirement is satisfied (e.g., record exists).
     */
    public function isSatisfied(): bool;
}
