<?php

declare(strict_types=1);

namespace Modules\Internship\Setup;

use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;

class InternshipSetupRequirement implements SetupRequirementProvider
{
    public function __construct(protected InternshipService $service) {}

    public function getRequirementIdentifier(): string
    {
        return AppSetupService::RECORD_INTERNSHIP;
    }

    public function isSatisfied(): bool
    {
        return $this->service->exists();
    }
}
