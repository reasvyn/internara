<?php

declare(strict_types=1);

namespace Modules\Setup\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Setup\Domain\Models\SetupProcess;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\SetupRequirementRegistry;
use Modules\Setup\Events\SetupFinalized;
use Modules\Exception\AppException;

/**
 * Application Service for orchestrating the setup process.
 * 
 * [S2 - Sustain] Implements the Application Layer by coordinating Domain Models and Infrastructure Services.
 * [S3 - Scalable] Uses Registry pattern to decouple module dependencies.
 */
class SetupOrchestrator implements SetupService
{
    public function __construct(
        protected SettingService $settingService,
        protected SetupRequirementRegistry $registry,
    ) {}

    /**
     * Reconstitutes the SetupProcess aggregate from infrastructure.
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
        $process = $this->getProcess();

        // [S1 - Secure] Atomic Concurrency Control
        $lock = \Illuminate\Support\Facades\Cache::lock("setup.step.{$step}", 30);

        return $lock->get(function () use ($step, $reqRecord, $process) {
            // Validate Domain Invariants via Aggregate
            if (!$process->canProceedTo($step)) {
                 throw new AppException(
                    userMessage: 'setup::exceptions.require_step_completed',
                    code: 403,
                );
            }

            // [S3 - Scalable] Automated Record Existence Validation
            $requiredRecord = $reqRecord ?? SetupProcess::STEP_RECORDS[$step] ?? null;
            if ($requiredRecord) {
                $exists = $this->isRecordExists($requiredRecord);
                
                // Enforce domain validation
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

            // Persist the state change
            $this->settingService->setValue("setup_step_{$step}", true);

            // Audit Trail
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
        $this->settingService->setValue($settings);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function finalizeSetupStep(): bool
    {
        return DB::transaction(function () {
            // Mark as installed
            $this->settingService->setValue(self::SETTING_APP_INSTALLED, true);
            $this->settingService->setValue(self::SETTING_SETUP_TOKEN, null);
            $this->settingService->setValue("setup_step_complete", true);

            // [S3 - Scalable] Event-Driven Finalization
            // Other modules (like School) should listen to this event to perform their 
            // specific finalization logic (e.g., setting brand name).
            event(new SetupFinalized());

            activity('setup')
                ->event('finalized')
                ->log(__('setup::wizard.audit_logs.finalized'));

            return true;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function generateTechnicalReport(): string
    {
        // ... Logic will be delegated to a specialized reporter later
        return "DDD-aligned Technical Report Placeholder";
    }
}
