<?php

declare(strict_types=1);

namespace Modules\School\Listeners;

use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Events\SetupFinalized;
use Modules\Setup\Services\Contracts\AppSetupService;

/**
 * Handles the SetupFinalized event to configure institutional branding.
 *
 * [S3 - Scalable] School-specific finalization logic decoupled from Setup module.
 */
class ConfigureInstitutionalBranding
{
    public function __construct(
        protected SchoolService $schoolService,
        protected SettingService $settingService,
    ) {}

    public function handle(SetupFinalized $event): void
    {
        $school = $this->schoolService->getSchool();

        if ($school) {
            $this->settingService->setValue([
                AppSetupService::SETTING_BRAND_NAME => $school->name,
                AppSetupService::SETTING_BRAND_LOGO => $school->logo_url ?? null,
            ]);
        }
    }
}
