<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Modules\Exception\AppException;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Domain\Models\SetupProcess;
use Modules\Setup\Events\SetupFinalized;
use Modules\Shared\Services\BaseService;

/**
 * Service implementation for handling the application setup process.
 * 
 * [S2 - Sustain] Aligned with project-wide Service Layer pattern.
 * [S3 - Scalable] Uses Registry pattern to decouple module dependencies.
 */
class SetupService extends BaseService implements Contracts\SetupService
{
    /**
     * Create a new SetupService instance.
     */
    public function __construct(
        protected SettingService $settingService,
        protected SetupRequirementRegistry $registry,
    ) {}

    /**
     * Reconstitutes the SetupProcess aggregate to validate state invariants.
     */
    protected function getProcess(): SetupProcess
    {
        $isInstalled = (bool) $this->settingService->getValue(self::SETTING_APP_INSTALLED, false);
        
        $steps = [
            self::STEP_WELCOME,
            self::STEP_ENVIRONMENT,
            self::STEP_SCHOOL,
            self::STEP_ACCOUNT,
            self::STEP_DEPARTMENT,
            self::STEP_INTERNSHIP,
            self::STEP_SYSTEM,
            self::STEP_COMPLETE,
        ];

        $completedSteps = [];
        foreach ($steps as $step) {
            $completedSteps[$step] = (bool) $this->settingService->getValue("setup_step_{$step}", false);
        }

        return SetupProcess::fromState($isInstalled, $completedSteps);
    }

    /**
     * {@inheritDoc}
     */
    public function isAppInstalled(bool $skipCache = true): bool
    {
        return $this->getProcess()->isInstalled();
    }

    /**
     * {@inheritDoc}
     */
    public function isStepCompleted(string $step, bool $skipCache = true): bool
    {
        return $this->getProcess()->isStepCompleted($step);
    }

    /**
     * {@inheritDoc}
     */
    public function isRecordExists(string $recordName): bool
    {
        // [S3 - Scalable] Delegation to Decoupled Registry
        return $this->registry->isRequirementSatisfied($recordName);
    }

    /**
     * {@inheritDoc}
     */
    public function requireSetupAccess(string $prevStep = ''): bool
    {
        $process = $this->getProcess();

        if ($process->isInstalled()) {
            return false;
        }

        if ($prevStep && !$process->isStepCompleted($prevStep)) {
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

        $process = $this->getProcess();

        // [S1 - Secure] Atomic Concurrency Control
        $lock = \Illuminate\Support\Facades\Cache::lock("setup.step.{$step}", 30);

        return $lock->get(function () use ($step, $reqRecord, $process) {
            if (!$process->canProceedTo($step)) {
                 throw new AppException(
                    userMessage: 'setup::exceptions.require_step_completed',
                    code: 403,
                );
            }

            $requiredRecord = $reqRecord ?? SetupProcess::STEP_RECORDS[$step] ?? null;
            if ($requiredRecord) {
                $exists = $this->isRecordExists($requiredRecord);
                
                try {
                    $process->validateStepFinalization($step, $exists);
                } catch (\DomainException $e) {
                    throw new AppException(
                        userMessage: 'setup::exceptions.require_record_exists',
                        logMessage: $e->getMessage(),
                        code: 403,
                    );
                }
            }

            if ($step === self::STEP_COMPLETE) {
                return $this->finalizeSetupStep();
            }

            $this->settingService->setValue("setup_step_{$step}", true);

            activity('setup')
                ->event('step_completed')
                ->withProperties(['step' => $step])
                ->log(__('setup::wizard.audit_logs.step_completed', ['step' => $step]));

            return true;
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

        return DB::transaction(function () {
            $this->settingService->setValue(self::SETTING_APP_INSTALLED, true);
            $this->settingService->setValue(self::SETTING_SETUP_TOKEN, null);
            $this->settingService->setValue("setup_step_complete", true);

            // [S3 - Scalable] Event-Driven Finalization
            event(new SetupFinalized());

            activity('setup')
                ->event('finalized')
                ->log(__('setup::wizard.audit_logs.finalized'));

            Session::forget(self::SESSION_SETUP_AUTHORIZED);
            Session::regenerate();

            \Illuminate\Support\Facades\Cache::forget('internara.installed');

            return true;
        });
    }
}
