<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire\Concerns;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Handles the core logic for multi-step application setup wizards.
 * This trait manages step progression, completion state, and redirection,
 * intended for use within Livewire components that represent a setup step.
 *
 * @mixin Component
 */
trait HandlesSetupSteps
{
    /**
     * The service responsible for handling setup business logic.
     */
    protected SetupService $setupService;

    /**
     * Holds the properties of the current setup step.
     */
    #[Locked]
    public array $setupStepProps = [];

    /**
     * Initializes the properties for the current setup step.
     *
     * @param string $currentStep The identifier for the current step.
     * @param string $nextStep The identifier for the next step.
     * @param string $prevStep The identifier for the previous step.
     * @param array<string, mixed> $extra Additional data for the step.
     */
    protected function initSetupStepProps(
        string $currentStep,
        string $nextStep = '',
        string $prevStep = '',
        array $extra = [],
    ): void {
        $this->setupStepProps = [
            'currentStep' => $currentStep,
            'nextStep' => $nextStep,
            'prevStep' => $prevStep,
            'extra' => $extra,
        ];
    }

    /**
     * Ensures the previous step was completed, redirecting if it was not.
     */
    protected function requireSetupAccess(): void
    {
        $prevStep = $this->setupStepProps['prevStep'] ?? null;

        if (!$this->setupService->requireSetupAccess($prevStep)) {
            $this->redirectToStep($prevStep);
        }
    }

    /**
     * Marks the current step as complete and proceeds to the next step.
     * Orchestrates the step progression, handling finalization if it's the 'complete' step.
     */
    public function nextStep(): void
    {
        $currentStep = $this->setupStepProps['currentStep'] ?? '';
        $nextStep = $this->setupStepProps['nextStep'] ?? '';
        $reqRecord = $this->setupStepProps['extra']['req_record'] ?? '';

        $success = $this->setupService->performSetupStep($currentStep, $reqRecord);

        if ($success && $currentStep === 'complete') {
            $this->redirectToLanding();

            return;
        }

        if ($success && $nextStep) {
            $this->redirectToStep($nextStep);
        }
    }

    /**
     * Redirects the user to the previous step in the setup process.
     */
    public function backToPrev(): void
    {
        $this->redirectToStep($this->setupStepProps['prevStep'] ?? 'welcome');
    }

    /**
     * Determines if the 'next step' button should be disabled.
     * The button is disabled if a required record for the current step does not exist.
     */
    #[Computed]
    public function disableNextStep(): bool
    {
        $record = $this->setupStepProps['extra']['req_record'] ?? null;

        return $record ? !$this->setupService->isRecordExists($record) : false;
    }

    /**
     * Redirects to a named setup step route.
     *
     * @param string $name The name of the step to redirect to.
     */
    protected function redirectToStep(string $name): void
    {
        if (empty($name)) {
            return;
        }

        $routeName = "setup.{$name}";
        $this->redirectRoute($routeName, navigate: true);
    }

    /**
     * Flushes the session and redirects to the application's landing page.
     */
    protected function redirectToLanding(): void
    {
        $landingRoute = $this->setupStepProps['extra']['landing_route'] ?? 'login';
        $this->redirectRoute($landingRoute, navigate: true);
    }
}
