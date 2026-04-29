<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Modules\Setup\Services\Contracts\InstallationAuditor;

/**
 * Welcome step - System requirements check
 *
 * [S1 - Secure] Pre-flight validation, no sensitive data exposure
 * [S2 - Sustain] Clear requirements display
 * [S3 - Scalable] Extensible audit system
 */
class SetupWelcome extends SetupWizardBase
{
    public array $requirements = [];
    public array $permissions = [];
    public array $database = [];
    public array $recommendations = [];
    public bool $hasErrors = false;

    public function mount(InstallationAuditor $auditor): void
    {
        $this->ensureNotInstalled();
        
        $audit = $auditor->audit();
        
        $this->requirements = $audit['requirements'];
        $this->permissions = $audit['permissions'];
        $this->database = $audit['database'];
        $this->recommendations = $audit['recommendations'] ?? [];
        
        $this->hasErrors = !$auditor->passes();
    }

    public function nextStep(): void
    {
        if ($this->hasErrors) {
            $this->addError('requirements', __('setup::wizard.requirements_not_met'));
            
            return;
        }

        $setup = $this->setupService->getSetup();
        $this->setupService->completeStep('welcome');

        $token = request()->get('token') ?? session('setup_token');
        
        $this->redirect(route('setup.school', ['token' => $token]));
    }

    public function render()
    {
        return view('setup::livewire.setup-welcome', [
            'progress' => $this->progress,
        ]);
    }
}
