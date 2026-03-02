<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Shared\Services\BaseService;

/**
 * Service implementation for handling the application setup process.
 */
class SetupService extends BaseService implements Contracts\SetupService
{
    /**
     * Setting key for application name.
     */
    public const SETTING_APP_NAME = 'app_name';

    /**
     * Create a new SetupService instance.
     */
    public function __construct(
        protected SettingService $settingService,
        protected SuperAdminService $superAdminService,
        protected SchoolService $schoolService,
        protected DepartmentService $departmentService,
        protected InternshipService $internshipService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function isAppInstalled(bool $skipCache = true): bool
    {
        return $this->settingService->getValue(self::SETTING_APP_INSTALLED, false, $skipCache);
    }

    /**
     * {@inheritDoc}
     */
    public function isStepCompleted(string $step, bool $skipCache = true): bool
    {
        if (empty($step)) {
            return true;
        }

        return $this->settingService->getValue("setup_step_{$step}", false, $skipCache);
    }

    /**
     * {@inheritDoc}
     */
    public function isRecordExists(string $recordName): bool
    {
        return match ($recordName) {
            self::RECORD_SUPER_ADMIN => $this->superAdminService->exists(),
            self::RECORD_SCHOOL => $this->schoolService->exists(),
            self::RECORD_DEPARTMENT => $this->departmentService->exists(),
            self::RECORD_INTERNSHIP => $this->internshipService->exists(),
            default => throw new InvalidArgumentException(
                "Unknown record type '{$recordName}' requested.",
            ),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function requireSetupAccess(string $prevStep = ''): bool
    {
        if (! $prevStep) {
            return ! $this->isAppInstalled();
        }

        if (! $this->isStepCompleted($prevStep, true)) {
            throw new AppException(
                userMessage: 'setup::exceptions.require_step_completed',
                code: 403,
            );
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function performSetupStep(string $step, ?string $reqRecord = null): bool
    {
        Gate::authorize('performStep', self::class);

        if ($step === self::STEP_COMPLETE) {
            return $this->finalizeSetupStep();
        }

        if ($reqRecord && ! $this->isRecordExists($reqRecord)) {
            throw new AppException(
                userMessage: 'setup::exceptions.require_record_exists',
                code: 403,
            );
        }

        return $this->storeStep($step);
    }

    /**
     * {@inheritDoc}
     */
    public function saveSystemSettings(array $settings): bool
    {
        Gate::authorize('saveSettings', self::class);

        $this->settingService->setValue($settings);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function finalizeSetupStep(): bool
    {
        Gate::authorize('finalize', self::class);

        return \Illuminate\Support\Facades\DB::transaction(function () {
            $schoolRecord = $this->schoolService->getSchool();
            $settings = [
                // [SYRS-C-004] Branding Invariant
                self::SETTING_BRAND_NAME => $schoolRecord->name,
                self::SETTING_BRAND_LOGO => $schoolRecord->logo_url ?? null,
                self::SETTING_SITE_TITLE => $schoolRecord->name . ' - ' . $this->settingService->getValue(self::SETTING_APP_NAME, 'Internara'),
                self::SETTING_APP_INSTALLED => true,
                self::SETTING_SETUP_TOKEN => null,
            ];

            $this->settingService->setValue($settings);

            // [S3 - Scalable] Dispatch finalization event
            event(new \Modules\Setup\Events\SetupFinalized(
                schoolName: $schoolRecord->name,
                installedAt: now()->toIso8601String(),
            ));

            // Targeted session cleanup
            Session::forget(self::SESSION_SETUP_AUTHORIZED);
            foreach (range(1, 8) as $step) {
                Session::forget("setup_step_{$step}");
            }

            Session::regenerate();

            return $this->isAppInstalled(true);
        });
    }

    /**
     * Stores the completion status of a setup step in the settings.
     */
    protected function storeStep(string $name, bool $completed = true): bool
    {
        $this->settingService->setValue("setup_step_{$name}", $completed);

        return true;
    }
}
