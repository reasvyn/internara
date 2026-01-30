<?php

declare(strict_types=1);

namespace Modules\Setup\Services\Contracts;

/**
 * Interface SetupService
 *
 * Defines the contract for handling application setup and initialization logic.
 */
interface SetupService
{
    /**
     * Standard setup steps.
     */
    public const STEP_WELCOME = 'welcome';

    public const STEP_ENVIRONMENT = 'environment';

    public const STEP_SCHOOL = 'school';

    public const STEP_ACCOUNT = 'account';

    public const STEP_DEPARTMENT = 'department';

    public const STEP_INTERNSHIP = 'internship';

    public const STEP_SYSTEM = 'system';

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

    /**
     * Session identifiers.
     */
    public const SESSION_SETUP_AUTHORIZED = 'setup_authorized';

    /**
     * Checks if the application is currently installed.
     *
     * @param bool $skipCache If true, bypasses the cache.
     */
    public function isAppInstalled(bool $skipCache = true): bool;

    /**
     * Checks if a specific setup step has been completed.
     *
     * @param string $step The name of the setup step to check.
     */
    public function isStepCompleted(string $step): bool;

    /**
     * Checks if a specific record exists in the system.
     *
     * @param string $recordName The name of the record to check.
     */
    public function isRecordExists(string $recordName): bool;

    /**
     * Requests access to the setup process, optionally checking against a previous step.
     */
    public function requireSetupAccess(string $prevStep = ''): bool;

    /**
     * Performs a specific setup step.
     */
    public function performSetupStep(string $step, ?string $reqRecord = null): bool;

    /**
     * Saves the system and SMTP settings.
     */
    public function saveSystemSettings(array $settings): bool;

    /**
     * Finalizes the current setup step.
     */
    public function finalizeSetupStep(): bool;
}