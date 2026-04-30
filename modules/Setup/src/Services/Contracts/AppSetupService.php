<?php

declare(strict_types=1);

namespace Modules\Setup\Services\Contracts;

use Modules\Shared\Contracts\WizardService;

/**
 * Interface AppSetupService
 *
 * Defines the contract for handling business/application configuration logic.
 * Formerly SetupService.
 */
interface AppSetupService extends WizardService
{
    /**
     * Standard setup steps.
     */
    public const STEP_WELCOME = 'welcome';

    public const STEP_SCHOOL = 'school';

    public const STEP_ACCOUNT = 'account';

    public const STEP_DEPARTMENT = 'department';

    public const STEP_INTERNSHIP = 'internship';

    public const STEP_COMPLETE = 'complete';

    /**
     * Required record type identifiers.
     */
    public const RECORD_SUPER_ADMIN = 'super-admin';

    public const RECORD_SCHOOL = 'school';

    public const RECORD_DEPARTMENT = 'department';

    public const RECORD_INTERNSHIP = 'internship';

    /**
     * Crucial setting keys.
     */
    public const SETTING_APP_INSTALLED = 'app_installed';

    public const SETTING_SETUP_TOKEN = 'setup_token';

    public const SETTING_SITE_TITLE = 'site_title';

    public const SETTING_BRAND_NAME = 'brand_name';

    public const SETTING_BRAND_LOGO = 'brand_logo';

    public const SETTING_APP_NAME = 'app_name';

    /**
     * Session identifiers.
     */
    public const SESSION_SETUP_AUTHORIZED = 'setup_authorized';

    /**
     * Checks if the application is currently marked as installed in the settings registry.
     */
    public function isAppInstalled(bool $skipCache = true): bool;

    /**
     * Checks if a specific setup step has been completed.
     */
    public function isStepCompleted(string $step, bool $skipCache = true): bool;

    /**
     * Checks if a specific required record exists in the system.
     */
    public function isRecordExists(string $recordName): bool;

    /**
     * Requests access to the setup process, optionally checking against a previous step.
     */
    public function requireSetupAccess(string $prevStep = ''): bool;

    /**
     * Performs a specific setup step's business logic.
     */
    public function performSetupStep(string $step, ?string $reqRecord = null): bool;

    /**
     * Finalizes the current setup step by persisting its completion state.
     */
    public function finalizeSetupStep(): bool;
}
