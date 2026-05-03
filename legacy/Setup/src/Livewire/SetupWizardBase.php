<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Shared\Livewire\Concerns\HandlesWizardSteps;

/**
 * Base class for Setup Wizard components
 *
 * [S1 - Secure] Authorization checks, token validation
 * [S2 - Sustain] Clear step progression logic
 * [S3 - Scalable] Stateless tokens, UUID support
 */
abstract class SetupWizardBase extends Component
{
    use HandlesWizardSteps;

    protected SetupService $setupService;

    public function boot(SetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * [S1 - Secure] Validate setup is not completed
     */
    protected function ensureNotInstalled(): void
    {
        if ($this->setupService->isInstalled()) {
            $this->redirectToDashboard();
        }
    }

    /**
     * [S1 - Secure] Validate step can be accessed
     */
    protected function authorizeStepAccess(string $step): void
    {
        $setup = $this->setupService->getSetup();

        // Check previous steps are completed
        $stepOrder = ['welcome', 'school', 'account', 'department', 'internship', 'complete'];
        $currentIndex = array_search($step, $stepOrder);

        if ($currentIndex === false) {
            $this->redirectToSetup();
        }

        for ($i = 0; $i < $currentIndex; $i++) {
            if (! $setup->isStepCompleted($stepOrder[$i])) {
                $this->redirectToSetup();
            }
        }
    }

    protected function redirectToDashboard(): void
    {
        $this->redirect(route('dashboard'));
    }

    protected function redirectToSetup(): void
    {
        $token = request()->get('token') ?? session('setup_token');
        $this->redirect(route('setup.welcome', ['token' => $token]));
    }

    /**
     * Get setup progress
     */
    public function getProgressProperty(): float
    {
        $setup = $this->setupService->getSetup();

        return $this->setupService->getProgress($setup);
    }
}
