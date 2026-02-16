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
     * Checks if the application is currently marked as installed in the settings registry.
     *
     * This check is used by the `RequireSetupAccess` middleware to prevent re-running
     * the installation wizard and to protect system settings from unauthorized overrides.
     *
     * @param bool $skipCache If true, bypasses the cache to ensure an authoritative state check.
     */
    public function isAppInstalled(bool $skipCache = true): bool;

    /**
     * Checks if a specific setup step has been completed.
     *
     * Used to enforce the sequential flow of the installation wizard, ensuring that
     * prerequisite data (like School info) is established before proceeding to
     * dependent steps (like Account creation).
     *
     * @param string $step The name of the setup step to check.
     * @param bool $skipCache If true, bypasses the cache to ensure an authoritative state check.
     */
    public function isStepCompleted(string $step, bool $skipCache = true): bool;

    /**
     * Checks if a specific required record exists in the system.
     *
     * Provides a safeguard during the setup process to verify that crucial entities
     * (SuperAdmin, School, etc.) have been physically persisted in the database.
     *
     * @param string $recordName The name of the record to check.
     */
    public function isRecordExists(string $recordName): bool;

    /**
     * Requests access to the setup process, optionally checking against a previous step.
     *
     * Acts as the primary authorization logic for setup routes. If the application is
     * already installed, it restricts access. If a previous step is required but
     * not finished, it throws an exception to preserve the installation sequence.
     */
    public function requireSetupAccess(string $prevStep = ''): bool;

    /**
     * Performs a specific setup step's business logic.
     *
     * Orchestrates the persistence of data for a given step and validates required
     * record existence before allowing the step to be marked as complete.
     */
    public function performSetupStep(string $step, ?string $reqRecord = null): bool;

    /**
     * Saves the system and SMTP settings provided by the user.
     *
     * Persists environmental and institutional configuration to the setting registry,
     * serving as the technical finalization of the system identity.
     */
    public function saveSystemSettings(array $settings): bool;

    /**
     * Finalizes the current setup step by persisting its completion state.
     *
     * Once called, the step is marked as complete in the registry, authorizing the
     * user to move to the next logical stage of the installation.
     */
    public function finalizeSetupStep(): bool;
}
