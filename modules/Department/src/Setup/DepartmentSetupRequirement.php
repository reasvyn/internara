<?php

declare(strict_types=1);

namespace Modules\Department\Setup;

use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;

class DepartmentSetupRequirement implements SetupRequirementProvider
{
    public function __construct(protected DepartmentService $service) {}

    public function getRequirementIdentifier(): string
    {
        return AppSetupService::RECORD_DEPARTMENT;
    }

    public function isSatisfied(): bool
    {
        return $this->service->exists();
    }
}
