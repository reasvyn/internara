<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Exception\AppException;
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
        if (!$prevStep) {
            return !$this->isAppInstalled();
        }

        if (!$this->isStepCompleted($prevStep, true)) {
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

        // [S1 - Secure] Atomic Step Locking
        // Prevents race conditions if multiple admins are accessing the setup suite simultaneously.
        $lock = \Illuminate\Support\Facades\Cache::lock("setup.step.{$step}", 30);

        return $lock->get(function () use ($step, $reqRecord) {
            if ($step === self::STEP_COMPLETE) {
                return $this->finalizeSetupStep();
            }

            if ($reqRecord && !$this->isRecordExists($reqRecord)) {
                throw new AppException(
                    userMessage: 'setup::exceptions.require_record_exists',
                    code: 403,
                );
            }

            $success = $this->storeStep($step);

            if ($success) {
                // [S2 - Sustain] Technical Step Logging
                // Switched from Log to activity() for UI-visible audit trail
                activity('setup')
                    ->event('step_completed')
                    ->withProperties(['step' => $step])
                    ->log(__('setup::wizard.audit_logs.step_completed', ['step' => $step]));
            }

            return $success;
        }) ?: throw new AppException(
            userMessage: 'setup::exceptions.concurrency_lock',
            code: 423,
        );
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

        // [S1 - Secure] Enterprise Concurrency Lock
        $lock = \Illuminate\Support\Facades\Cache::lock('setup.finalizing', 60);

        return $lock->get(function () {
            return \Illuminate\Support\Facades\DB::transaction(function () {
                $schoolRecord = $this->schoolService->getSchool();
                
                if (! $schoolRecord) {
                    throw new AppException(
                        userMessage: 'setup::exceptions.require_record_exists',
                        code: 403,
                    );
                }

                $settings = [
                    // [SYRS-C-004] Branding Invariant
                    self::SETTING_BRAND_NAME => $schoolRecord->name,
                    self::SETTING_BRAND_LOGO => $schoolRecord->logo_url ?? null,
                    self::SETTING_SITE_TITLE =>
                        $schoolRecord->name .
                        ' - ' .
                        $this->settingService->getValue(self::SETTING_APP_NAME, 'Internara'),
                    self::SETTING_APP_INSTALLED => true,
                    self::SETTING_SETUP_TOKEN => null,
                ];

                $this->settingService->setValue($settings);

                // [S3 - Scalable] Dispatch finalization event
                event(
                    new \Modules\Setup\Events\SetupFinalized(
                        schoolName: $schoolRecord->name,
                        installedAt: now()->toIso8601String(),
                    ),
                );

                // [S2 - Sustain] Log finalization
                activity('setup')
                    ->event('finalized')
                    ->log(__('setup::wizard.audit_logs.finalized'));

                // Enterprise-grade session cleanup
                $this->cleanupSetupSessions();

                Session::regenerate();

                $this->storeStep('complete');

                // Force cache refresh for app_installed
                $this->settingService->setValue(self::SETTING_APP_INSTALLED, true);
                \Illuminate\Support\Facades\Cache::forget('internara.installed');

                return $this->isAppInstalled(true);
            });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function generateTechnicalReport(): string
    {
        $auditor = app(\Modules\Setup\Services\Contracts\SystemAuditor::class);
        $audit = $auditor->audit();
        $appName = $this->settingService->getValue(self::SETTING_APP_NAME, 'Internara');
        $now = now()->toDayDateTimeString();

        $report = "INTERNARA INSTALLATION REPORT\n";
        $report .= "============================\n";
        $report .= "Application: {$appName}\n";
        $report .= "Generated At: {$now}\n";
        $report .= "Environment: " . config('app.env') . "\n\n";

        $report .= "1. INFRASTRUCTURE AUDIT\n";
        $report .= "-----------------------\n";
        foreach ($audit['requirements'] as $label => $passed) {
            $report .= sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $label);
        }

        $report .= "\n2. PERMISSIONS AUDIT\n";
        $report .= "--------------------\n";
        foreach ($audit['permissions'] as $label => $passed) {
            $report .= sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $label);
        }

        $report .= "\n3. DATABASE CONNECTIVITY\n";
        $report .= "-----------------------\n";
        $report .= "Status: " . ($audit['database']['connection'] ? 'CONNECTED' : 'DISCONNECTED') . "\n";
        $report .= "Detail: " . $audit['database']['message'] . "\n\n";

        $report .= "4. SETUP PROGRESSION\n";
        $report .= "--------------------\n";
        $steps = [
            self::STEP_WELCOME,
            self::STEP_ENVIRONMENT,
            self::STEP_SCHOOL,
            self::STEP_ACCOUNT,
            self::STEP_DEPARTMENT,
            self::STEP_INTERNSHIP,
            self::STEP_SYSTEM,
            'complete',
        ];

        foreach ($steps as $step) {
            $completed = $this->isStepCompleted($step);
            $report .= sprintf("[%s] Step: %s\n", $completed ? 'DONE' : 'PENDING', strtoupper($step));
        }

        $report .= "\n============================\n";
        $report .= "END OF REPORT\n";

        return $report;
    }
    /**
     * Stores the completion status of a setup step in the settings.
     */
    protected function storeStep(string $name, bool $completed = true): bool
    {
        $this->settingService->setValue("setup_step_{$name}", $completed);

        return true;
    }

    /**
     * Thoroughly cleans up all setup-related session data.
     */
    protected function cleanupSetupSessions(): void
    {
        Session::forget(self::SESSION_SETUP_AUTHORIZED);

        $keys = Session::all();
        foreach (array_keys($keys) as $key) {
            if (str_starts_with($key, 'setup_step_')) {
                Session::forget($key);
            }
        }
    }
}
