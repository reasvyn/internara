<?php

declare(strict_types=1);

namespace Modules\School\Setup;

use Modules\School\Services\Contracts\SchoolService;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Provides setup requirement validation for the School module.
 * 
 * [S3 - Scalable] Implementation of the decoupled requirement provider.
 */
class SchoolSetupRequirement implements SetupRequirementProvider
{
    public function __construct(
        protected SchoolService $schoolService
    ) {}

    public function getRequirementIdentifier(): string
    {
        return SetupService::RECORD_SCHOOL;
    }

    public function isSatisfied(): bool
    {
        return $this->schoolService->exists();
    }
}
