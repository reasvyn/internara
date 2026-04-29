<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

/**
 * Setup Complete - Finalization step
 *
 * [S1 - Secure] Server-side mandates, token cleanup
 * [S2 - Sustain] Clear completion logic
 * [S3 - Scalable] Event dispatch, audit logging
 */
class SetupComplete extends SetupWizardBase
{
    public bool $dataVerified = false;
    public bool $securityAware = false;
    public bool $legalAgreed = false;

    public function mount(): void
    {
        $this->authorizeStepAccess('complete');
        $this->ensureNotInstalled();
    }

    public function completeSetup(): void
    {
        // [S1 - Secure] Server-side mandate enforcement
        $this->validate([
            'dataVerified' => 'accepted',
            'securityAware' => 'accepted',
            'legalAgreed' => 'accepted',
        ], [
            'dataVerified.accepted' => __('setup::validation.complete.verify_data'),
            'securityAware.accepted' => __('setup::validation.complete.security_aware'),
            'legalAgreed.accepted' => __('setup::validation.complete.legal_agree'),
        ]);

        $setup = $this->setupService->getSetup();
        
        // Get admin ID from completed steps
        $adminId = $setup->admin_id;
        
        if (!$adminId) {
            $this->addError('general', __('setup::messages.admin_not_found'));
            return;
        }

        // Finalize (atomic operation, clears tokens)
        $this->setupService->finalize($setup, $adminId);

        // Clear session
        session()->forget(['setup_token', 'setup_authorized']);

        // Redirect to login
        $this->redirect(route('login'));
    }

    public function render()
    {
        $setup = $this->setupService->getSetup();
        
        return view('setup::livewire.setup-complete', [
            'progress' => $this->progress,
            'setup' => $setup,
            'schoolName' => $setup->school?->name ?? '-',
        ]);
    }
}
