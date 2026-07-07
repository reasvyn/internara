<?php

declare(strict_types=1);

namespace App\Core\Livewire;

use App\Core\Exceptions\RejectedException;
use Livewire\Component;

/**
 * Base class for multi-step wizard components.
 *
 * Provides:
 * - Step navigation (next, previous, go-to-step)
 * - Step progress tracking and completion history
 * - Step-specific validation via validateCurrentStep()
 * - State persistence hooks (save/restore between steps)
 * - RejectedException handling
 *
 * Examples: SetupWizard, multi-step registration forms
 */
abstract class BaseWizard extends Component
{
    /** @var int Current step number (1-indexed) */
    public int $currentStep = 1;

    /** @var array<int, bool> Completed steps tracking */
    public array $completedSteps = [];

    abstract protected function steps(): array;

    public function nextStep(): void
    {
        if ($this->currentStep >= count($this->steps())) {
            return;
        }

        $this->validateCurrentStep();

        $this->markStepCompleted($this->currentStep);
        $this->currentStep++;
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > count($this->steps())) {
            return;
        }

        if ($this->isStepAccessible($step)) {
            $this->currentStep = $step;
        }
    }

    public function isStepAccessible(int $step): bool
    {
        for ($i = 1; $i < $step; $i++) {
            if (!($this->completedSteps[$i] ?? false)) {
                return false;
            }
        }

        return true;
    }

    public function isStepCompleted(int $step): bool
    {
        return $this->completedSteps[$step] ?? false;
    }

    public function isCurrentStep(int $step): bool
    {
        return $this->currentStep === $step;
    }

    public function progressPercent(): int
    {
        $total = count($this->steps());

        if ($total <= 1) {
            return 100;
        }

        return (int) round((($this->currentStep - 1) / ($total - 1)) * 100);
    }

    public function currentStepKey(): string
    {
        $keys = $this->steps();

        return $keys[$this->currentStep - 1] ?? '';
    }

    protected function markStepCompleted(int $step): void
    {
        $this->completedSteps[$step] = true;
    }

    protected function validateCurrentStep(): void
    {
        // Override in subclasses to validate step-specific logic
    }

    protected function handleStepError(callable $callback): void
    {
        try {
            $callback();
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        } catch (\Throwable $e) {
            flash()->error(__('common.actions.error_occurred'));
        }
    }
}
