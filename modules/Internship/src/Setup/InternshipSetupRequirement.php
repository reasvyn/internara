<?php

declare(strict_types=1);

namespace Modules\Internship\Setup;

use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;
use Modules\Setup\Services\Contracts\SetupService;

class InternshipSetupRequirement implements SetupRequirementProvider
{
    public function __construct(protected InternshipService $service) {}

    public function getRequirementIdentifier(): string
    {
        return SetupService::RECORD_INTERNSHIP;
    }

    public function isSatisfied(): bool
    {
        return $this->service->exists();
    }
}
