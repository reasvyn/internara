<?php

declare(strict_types=1);

namespace Modules\Shared\Livewire\Concerns;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Exception\AppException;
use Modules\Setup\Services\Contracts\AppSetupService;

/**
 * Handles the core logic for multi-step application wizards (Installation/Setup).
 * This trait manages step progression, completion state, and redirection.
 *
 * [S2 - Sustain] Moved to Shared module to support both Installation and Setup modules.
 *
 * @mixin Component
 */
trait HandlesWizardSteps
{
    /**
     * The service responsible for handling setup/config business logic.
     */
    protected AppAppSetupService $setupService;

    /**
     * Holds the properties of the current wizard step.
     */
    #[Locked]
    public array $wizardStepProps = [];

    /**
     * Initializes the properties for the current wizard step.
     *
     * @param string $currentStep The identifier for the current step.
     * @param string $nextStep The identifier for the next step.
     * @param string $prevStep The identifier for the previous step.
     * @param array<string, mixed> $extra Additional data for the step.
     */
    protected function initWizardStepProps(
        string $currentStep,
        string $nextStep = '',
        string $prevStep = '',
        array $extra = [],
    ): void {
        $this->wizardStepProps = [
            'currentStep' => $currentStep,
            'nextStep' => $nextStep,
            'prevStep' => $prevStep,
            'extra' => $extra,
        ];
    }

    /**
     * Ensures the previous step was completed, and the user is authorized,
     * redirecting if they are not.
     */
    protected function requireWizardAccess(): void
    {
        if (! session()->get(AppAppSetupService::SESSION_SETUP_AUTHORIZED)) {
            $this->redirectRoute('setup', navigate: true);

            return;
        }

        $prevStep = $this->wizardStepProps['prevStep'] ?? null;

        try {
            if (! $this->setupService->requireSetupAccess($prevStep)) {
                $this->redirectToStep($prevStep ?: 'setup');
            }
        } catch (AppException $e) {
            report($e);
            $this->redirectToStep($prevStep ?: 'setup');
        }
    }

    /**
     * Marks the current step as complete and proceeds to the next step.
     */
    public function nextStep(): void
    {
        $currentStep = $this->wizardStepProps['currentStep'] ?? '';
        $nextStep = $this->wizardStepProps['nextStep'] ?? '';
        $reqRecord = $this->wizardStepProps['extra']['req_record'] ?? '';

        try {
            $success = $this->setupService->performSetupStep($currentStep, $reqRecord);

            if ($success) {
                flash()->success(__('setup::wizard.common.step_success', [
                    'step' => __('setup::wizard.'.$currentStep.'.title'),
                ]));

                if ($currentStep === AppAppSetupService::STEP_COMPLETE) {
                    $this->redirectToLanding();

                    return;
                }

                if ($nextStep) {
                    $this->redirectToStep($nextStep);
                }
            }
        } catch (\Exception $e) {
            if ($e instanceof AppException) {
                flash()->error($e->getUserMessage());
            } else {
                report($e);
                flash()->error(__('ui::errors.unexpected_technical_failure'));
            }
        }
    }

    /**
     * Redirects the user to the previous step.
     */
    public function backToPrev(): void
    {
        $this->redirectToStep($this->wizardStepProps['prevStep'] ?? 'setup');
    }

    /**
     * Determines if the required record for the current step exists.
     */
    #[Computed]
    public function isRecordExists(): bool
    {
        $record = $this->wizardStepProps['extra']['req_record'] ?? null;

        return $record ? $this->setupService->isRecordExists($record) : true;
    }

    /**
     * Determines if the 'next step' button should be disabled.
     */
    #[Computed]
    public function disableNextStep(): bool
    {
        $record = $this->wizardStepProps['extra']['req_record'] ?? null;

        return $record ? ! $this->setupService->isRecordExists($record) : false;
    }

    /**
     * Re-evaluates the step completion status.
     */
    public function updateStepStatus(): void
    {
        unset($this->disableNextStep);
    }

    /**
     * Redirects to a named step route.
     */
    protected function redirectToStep(string $name): void
    {
        if (empty($name)) {
            return;
        }

        $routeName = str_contains($name, '.') ? $name : "setup.{$name}";
        $this->redirectRoute($routeName, navigate: true);
    }

    /**
     * Flushes the session and redirects to the application's landing page.
     */
    protected function redirectToLanding(): void
    {
        $landingRoute = $this->wizardStepProps['extra']['landing_route'] ?? 'login';
        $this->redirectRoute($landingRoute, navigate: true);
    }
}
