<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\Support\Facades\Hash;
use Modules\User\Models\User;
use Modules\Profile\Models\Profile;
use Modules\Admin\Services\Contracts\SuperAdminService;

/**
 * Account Setup - Create super admin
 *
 * [S1 - Secure] Encrypted PII, hashed password, UUID generation
 * [S2 - Sustain] Clear validation, audit logging
 * [S3 - Scalable] Service contract usage, event dispatch
 */
class AccountSetup extends SetupWizardBase
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    public function mount(): void
    {
        $this->authorizeStepAccess('account');
        $this->ensureNotInstalled();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'passwordConfirmation' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('setup::validation.account.name_required'),
            'email.required' => __('setup::validation.account.email_required'),
            'email.email' => __('setup::validation.account.email_invalid'),
            'email.unique' => __('setup::validation.account.email_taken'),
            'password.required' => __('setup::validation.account.password_required'),
            'password.min' => __('setup::validation.account.password_min'),
            'passwordConfirmation.required' => __('setup::validation.account.confirm_password'),
            'passwordConfirmation.same' => __('setup::validation.account.password_mismatch'),
        ];
    }

    public function saveAccount(SuperAdminService $adminService): void
    {
        $validated = $this->validate();

        $user = $adminService->createSuperAdmin([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $this->setupService->completeStep('account', [
            'admin_id' => $user->id,
        ]);

        $token = request()->get('token') ?? session('setup_token');
        
        $this->redirect(route('setup.department', ['token' => $token]));
    }

    public function render()
    {
        return view('setup::livewire.account-setup', [
            'progress' => $this->progress,
        ]);
    }
}
