<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Modules\Exception\AppException;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Domain\Models\SetupProcess;
use Modules\Setup\Events\SetupFinalized;
use Modules\Setup\Services\Contracts\AppSetupService as Contract;
use Modules\Shared\Services\BaseService;

/**
 * Service implementation for handling the application setup process.
 *
 * [S2 - Sustain] Aligned with project-wide Service Layer pattern.
 * [S3 - Scalable] Uses Registry pattern to decouple module dependencies.
 */
class AppSetupService extends BaseService implements Contract
{
    /**
     * Create a new AppSetupService instance.
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
            self::STEP_SCHOOL,
            self::STEP_ACCOUNT,
            self::STEP_DEPARTMENT,
            self::STEP_INTERNSHIP,
            self::STEP_COMPLETE,
        ];

        $completedSteps = [];
        foreach ($steps as $step) {
            $completedSteps[$step] = (bool) $this->settingService->getValue(
                "setup_step_{$step}",
                false,
            );
        }

        return SetupProcess::fromState($isInstalled, $completedSteps);
    }

    /**
     * {@inheritdoc}
     */
    public function isAppInstalled(bool $skipCache = true): bool
    {
        return $this->getProcess()->isInstalled();
    }

    /**
     * {@inheritdoc}
     */
    public function isStepCompleted(string $step, bool $skipCache = true): bool
    {
        return $this->getProcess()->isStepCompleted($step);
    }

    /**
     * {@inheritdoc}
     */
    public function isRecordExists(string $recordName): bool
    {
        return $this->registry->isRequirementSatisfied($recordName);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function performSetupStep(string $step, ?string $reqRecord = null): bool
    {
        Gate::authorize('performStep', self::class);

        $process = $this->getProcess();

        $lock = Cache::lock("setup.step.{$step}", 30);

        return $lock->get(function () use ($step, $reqRecord, $process) {
            if (!$process->canProceedTo($step)) {
                throw new AppException(
                    userMessage: 'setup::exceptions.require_step_completed',
                    code: 403,
                );
            }

            $requiredRecord = $reqRecord ?? (SetupProcess::STEP_RECORDS[$step] ?? null);
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
        }) ?:
            throw new AppException(userMessage: 'setup::exceptions.concurrency_lock', code: 423);
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeSetupStep(): bool
    {
        Gate::authorize('finalize', self::class);

        return DB::transaction(function () {
            $this->settingService->setValue(self::SETTING_APP_INSTALLED, true);
            $this->settingService->setValue(self::SETTING_SETUP_TOKEN, null);
            $this->settingService->setValue('setup_step_complete', true);

            event(new SetupFinalized());

            activity('setup')->event('finalized')->log(__('setup::wizard.audit_logs.finalized'));

            Session::forget(self::SESSION_SETUP_AUTHORIZED);
            Session::regenerate();

            Cache::forget('internara.installed');

            return true;
        });
    }
}
