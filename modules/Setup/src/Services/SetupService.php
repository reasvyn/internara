<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Exception\AppException;
use Modules\Exception\RecordNotFoundException;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\User\Services\Contracts\SuperAdminService;

/**
 * Service implementation for handling the application setup process.
 */
class SetupService implements Contracts\SetupService
{
    /**
     * Create a new SetupService instance.
     *
     * @param SettingService $settingService The setting service instance.
     * @param SuperAdminService $superAdminService The SuperAdmin service instance.
     * @param SchoolService $schoolService The school service instance.
     * @param DepartmentService $departmentService The department service instance.
     * @param InternshipService $internshipService The internship service instance.
     */
    public function __construct(
        protected SettingService $settingService,
        protected SuperAdminService $superAdminService,
        protected SchoolService $schoolService,
        protected DepartmentService $departmentService,
        protected InternshipService $internshipService,
    ) {}

    /**
     * Checks if the application is currently installed.
     *
     * @param bool $skipCache If true, bypasses the cache and re-checks the installation status.
     *
     * @return bool True if the application is installed, false otherwise.
     */
    public function isAppInstalled(bool $skipCache = true): bool
    {
        return $this->settingService->getValue('app_installed', false, $skipCache);
    }

    /**
     * Checks if a specific setup step has been completed.
     *
     * @param string $step The name of the setup step to check.
     *
     * @return bool True if the step is completed, false otherwise.
     */
    public function isStepCompleted(string $step): bool
    {
        if (empty($step)) {
            return true;
        }

        return Session::get("setup:{$step}", false);
    }

    /**
     * Checks if a specific record exists in the system.
     *
     * @param string $recordName The name of the record to check for existence (e.g., 'super-admin', 'school').
     *
     * @throws InvalidArgumentException If an unknown record type is requested.
     *
     * @return bool True if the record exists, false otherwise.
     */
    public function isRecordExists(string $recordName): bool
    {
        try {
            return match ($recordName) {
                'super-admin' => $this->superAdminService->exists(),
                'school' => $this->schoolService->exists(),
                'department' => $this->departmentService->exists(),
                'internship' => $this->internshipService->exists(),
                default => throw new InvalidArgumentException(
                    "Unknown record type '{$recordName}' requested.",
                ),
            };
        } catch (RecordNotFoundException $e) {
            return false; // If exists() throws RecordNotFoundException, it means the record does not exist.
        }
    }

    /**
     * Requests access to the setup process, optionally checking against a previous step.
     *
     * @param string $prevStep The name of the previous step, if applicable.
     *
     * @throws AppException If the previous step is not completed, preventing access.
     *
     * @return bool True if setup access is granted, false otherwise.
     */
    public function requireSetupAccess(string $prevStep = ''): bool
    {
        if (! $prevStep) {
            return $this->isAppInstalled();
        }

        if (! ($access = $this->isStepCompleted($prevStep))) {
            throw new AppException(
                userMessage: 'setup::exceptions.require_step_completed',
                code: 403,
            );
        }

        return $access;
    }

    /**
     * Performs a specific setup step.
     *
     * @param string $step The name of the setup step to perform.
     * @param string|null $requireRecord Optional. The name of a required record for this step.
     *
     * @return bool True if the step was performed successfully, false otherwise.
     */
    public function performSetupStep(string $step, ?string $requireRecord = null): bool
    {
        $perform = function () use ($step, $requireRecord) {
            if ($requireRecord && ! $this->isRecordExists($requireRecord)) {
                throw new AppException(
                    userMessage: 'setup::exceptions.require_record_exists',
                    code: 403,
                );
            }

            return $this->storeStep($step);
        };

        return match ($step) {
            'system' => $this->storeStep('system'),
            'complete' => $this->finalizeSetupStep(),
            default => $perform(),
        };
    }

    /**
     * Saves the system and SMTP settings.
     */
    public function saveSystemSettings(array $settings): bool
    {
        $this->settingService->setValue($settings);

        return true;
    }

    /**
     * Finalizes the current setup step.
     *
     * @return bool True if the setup step was finalized successfully, false otherwise.
     */
    public function finalizeSetupStep(): bool
    {
        $schoolRecord = $this->schoolService->getSchool();
        $settings = [
            'brand_name' => $schoolRecord->name,
            'brand_logo' => $schoolRecord->logo_url ?? null,
            'site_title' => $schoolRecord->name.' - Sistem Informasi Manajemen PKL',
            'app_installed' => true,
            'setup_token' => null, // Purge the token
        ];

        $this->settingService->setValue($settings);

        Session::forget('setup_authorized'); // Clear authorized session
        Session::flush();
        Session::regenerate();

        return $this->isAppInstalled();
    }

    /**
     * Stores the completion status of a setup step in the session.
     *
     * @param string $name The name of the step.
     * @param bool $completed The completion status.
     */
    protected function storeStep(string $name, bool $completed = true): bool
    {
        Session::put("setup:{$name}", $completed);

        return true;
    }
}
