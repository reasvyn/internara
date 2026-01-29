<?php

declare(strict_types=1);

namespace Modules\Setup\Services\Contracts;

/**
 * Defines the contract for the service that handles application setup logic.
 */
interface SetupService
{
    /**
     * Checks if the application is currently installed.
     *
     * @param bool $skipCache If true, bypasses the cache and re-checks the installation status.
     *
     * @return bool True if the application is installed, false otherwise.
     */
    public function isAppInstalled(bool $skipCache = true): bool;

    /**
     * Checks if a specific setup step has been completed.
     *
     * @param string $step The name of the setup step to check.
     *
     * @return bool True if the step is completed, false otherwise.
     */
    public function isStepCompleted(string $step): bool;

    /**
     * Checks if a specific record exists in the system.
     *
     * @param string $recordName The name of the record to check for existence.
     *
     * @return bool True if the record exists, false otherwise.
     */
    public function isRecordExists(string $recordName): bool;

    /**
     * Requests access to the setup process, optionally checking against a previous step.
     *
     * @param string $prevStep The name of the previous step, if applicable.
     *
     * @return bool True if setup access is granted, false otherwise.
     */
    public function requireSetupAccess(string $prevStep = ''): bool;

    /**
     * Performs a specific setup step.
     *
     * @param string $step The name of the setup step to perform.
     * @param string|null $reqRecord Optional. The name of a required record for this step.
     *
     * @return bool True if the step was performed successfully, false otherwise.
     */
    public function performSetupStep(string $step, ?string $reqRecord = null): bool;

    /**
     * Saves the system and SMTP settings.
     *
     * @param array<string, mixed> $settings The settings to save.
     */
    public function saveSystemSettings(array $settings): bool;

    /**
     * Finalizes the current setup step.
     *
     * @return bool True if the setup step was finalized successfully, false otherwise.
     */
    public function finalizeSetupStep(): bool;
}
