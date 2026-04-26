<?php

declare(strict_types=1);

namespace Modules\Setup\Domain\Models;

use InvalidArgumentException;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Aggregate Root representing the application setup process.
 * 
 * [S2 - Sustain] Manages the lifecycle and state invariants of the setup wizard.
 */
class SetupProcess
{
    /**
     * @param array<string, bool> $completedSteps
     */
    public function __construct(
        protected bool $isInstalled,
        protected array $completedSteps = [],
    ) {}

    /**
     * Factory method to create from raw state.
     */
    public static function fromState(bool $isInstalled, array $completedSteps): self
    {
        return new self($isInstalled, $completedSteps);
    }

    /**
     * Determines if the setup process can proceed to a specific step.
     */
    public function canProceedTo(string $step): bool
    {
        if ($this->isInstalled) {
            return false;
        }

        $prevStep = $this->getPreviousStepFor($step);
        if ($prevStep === null) {
            return true;
        }

        return $this->isStepCompleted($prevStep);
    }

    /**
     * Marks a step as completed.
     */
    public function completeStep(string $step): void
    {
        if ($this->isInstalled) {
            throw new \LogicException('Cannot modify setup steps after application is installed.');
        }

        $this->completedSteps[$step] = true;
    }

    /**
     * Checks if a step is completed.
     */
    public function isStepCompleted(string $step): bool
    {
        return $this->completedSteps[$step] ?? false;
    }

    /**
     * Checks if the application is fully installed.
     */
    public function isInstalled(): bool
    {
        return $this->isInstalled;
    }

    /**
     * Gets the logical previous step for a given step.
     */
    protected function getPreviousStepFor(string $step): ?string
    {
        return match ($step) {
            SetupService::STEP_WELCOME => null,
            SetupService::STEP_ENVIRONMENT => SetupService::STEP_WELCOME,
            SetupService::STEP_SCHOOL => SetupService::STEP_ENVIRONMENT,
            SetupService::STEP_ACCOUNT => SetupService::STEP_SCHOOL,
            SetupService::STEP_DEPARTMENT => SetupService::STEP_ACCOUNT,
            SetupService::STEP_INTERNSHIP => SetupService::STEP_DEPARTMENT,
            SetupService::STEP_SYSTEM => SetupService::STEP_INTERNSHIP,
            SetupService::STEP_COMPLETE => SetupService::STEP_SYSTEM,
            default => throw new InvalidArgumentException("Unknown setup step: {$step}"),
        };
    }

    /**
     * Returns the raw state for persistence.
     */
    public function toArray(): array
    {
        return [
            'is_installed' => $this->isInstalled,
            'completed_steps' => $this->completedSteps,
        ];
    }
}
