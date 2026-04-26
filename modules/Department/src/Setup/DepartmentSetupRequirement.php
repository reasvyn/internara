<?php

declare(strict_types=1);

namespace Modules\Department\Setup;

use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;
use Modules\Setup\Services\Contracts\SetupService;

class DepartmentSetupRequirement implements SetupRequirementProvider
{
    public function __construct(protected DepartmentService $service) {}

    public function getRequirementIdentifier(): string
    {
        return SetupService::RECORD_DEPARTMENT;
    }

    public function isSatisfied(): bool
    {
        return $this->service->exists();
    }
}
