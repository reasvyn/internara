<?php

declare(strict_types=1);

namespace Modules\Admin\Setup;

use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;

class AdminSetupRequirement implements SetupRequirementProvider
{
    public function __construct(protected SuperAdminService $service) {}

    public function getRequirementIdentifier(): string
    {
        return AppSetupService::RECORD_SUPER_ADMIN;
    }

    public function isSatisfied(): bool
    {
        return $this->service->exists();
    }
}
