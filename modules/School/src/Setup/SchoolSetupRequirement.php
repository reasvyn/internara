<?php

declare(strict_types=1);

namespace Modules\School\Setup;

use Modules\School\Services\Contracts\SchoolService;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;

/**
 * Provides setup requirement validation for the School module.
 *
 * [S3 - Scalable] Implementation of the decoupled requirement provider.
 */
class SchoolSetupRequirement implements SetupRequirementProvider
{
    public function __construct(protected SchoolService $schoolService) {}

    public function getRequirementIdentifier(): string
    {
        return AppSetupService::RECORD_SCHOOL;
    }

    public function isSatisfied(): bool
    {
        return $this->schoolService->exists();
    }
}
