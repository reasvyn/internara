<?php

declare(strict_types=1);

namespace Modules\Setup\Domain\Models;

use InvalidArgumentException;
use Modules\Setup\Services\Contracts\AppSetupService;

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
     * Step-to-Record mapping for automated existence checks.
     */
    public const STEP_RECORDS = [
        AppAppSetupService::STEP_SCHOOL => AppAppSetupService::RECORD_SCHOOL,
        AppAppSetupService::STEP_ACCOUNT => AppAppSetupService::RECORD_SUPER_ADMIN,
        AppAppSetupService::STEP_DEPARTMENT => AppAppSetupService::RECORD_DEPARTMENT,
        AppAppSetupService::STEP_INTERNSHIP => AppAppSetupService::RECORD_INTERNSHIP,
    ];

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

        // [S1 - Secure] Sequential integrity invariant
        return $this->isStepCompleted($prevStep);
    }

    /**
     * Validates if a step can be finalized based on its requirements.
     */
    public function validateStepFinalization(string $step, bool $recordExists): void
    {
        $requiredRecord = self::STEP_RECORDS[$step] ?? null;

        if ($requiredRecord && !$recordExists) {
            throw new \DomainException(
                "Cannot finalize step '{$step}': required record '{$requiredRecord}' is missing.",
            );
        }
    }

    /**
     * Marks a step as completed.
     */
    public function completeStep(string $step): void
    {
        if ($this->isInstalled) {
            throw new \LogicException('Cannot modify setup steps after application is installed.');
        }

        if (!$this->canProceedTo($step)) {
            throw new \DomainException(
                "Cannot complete step '{$step}': previous steps not finished.",
            );
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
            AppAppSetupService::STEP_SCHOOL => null,
            AppAppSetupService::STEP_ACCOUNT => AppAppSetupService::STEP_SCHOOL,
            AppAppSetupService::STEP_DEPARTMENT => AppAppSetupService::STEP_ACCOUNT,
            AppAppSetupService::STEP_INTERNSHIP => AppAppSetupService::STEP_DEPARTMENT,
            AppAppSetupService::STEP_SYSTEM => AppAppSetupService::STEP_INTERNSHIP,
            AppAppSetupService::STEP_COMPLETE => AppAppSetupService::STEP_SYSTEM,
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
