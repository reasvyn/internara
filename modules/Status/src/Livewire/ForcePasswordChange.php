<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Modules\Status\Services\PasswordPolicyService;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

/**
 * ForcePasswordChangeComponent
 *
 * Modal form for forcing users to change expired passwords.
 * Shown immediately when user logs in with expired password.
 * Cannot be dismissed - user must change password to proceed.
 */
class ForcePasswordChange extends Component
{
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public bool $showPassword = false;
    public bool $showCurrentPassword = false;

    public string $errorMessage = '';
    public bool $isLoading = false;

    private PasswordPolicyService $passwordPolicyService;

    protected array $rules = [
        'currentPassword' => ['required', 'string'],
        'newPassword' => [
            'required',
            'string',
            'min:12',
            'regex:/[A-Z]/', // At least one uppercase
            'regex:/[a-z]/', // At least one lowercase
            'regex:/[0-9]/', // At least one number
            'regex:/[!@#$%^&*]/', // At least one special character
            'different:currentPassword',
        ],
        'newPasswordConfirmation' => ['required', 'same:newPassword'],
    ];

    protected array $messages = [
        'currentPassword.required' => 'Current password is required.',
        'newPassword.required' => 'New password is required.',
        'newPassword.min' => 'Password must be at least 12 characters.',
        'newPassword.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
        'newPassword.different' => 'New password must be different from current password.',
        'newPasswordConfirmation.same' => 'Passwords do not match.',
    ];

    public function mount(PasswordPolicyService $passwordPolicyService): void
    {
        $this->passwordPolicyService = $passwordPolicyService;
    }

    public function render()
    {
        $user = auth()->user();
        $daysUntilExpiry = $this->passwordPolicyService->getDaysUntilExpiry($user);
        $isExpired = $this->passwordPolicyService->isExpired($user);

        return view('livewire.force-password-change', [
            'isExpired' => $isExpired,
            'daysUntilExpiry' => $daysUntilExpiry,
        ]);
    }

    /**
     * Validate and update password
     */
    public function changePassword(): void
    {
        $this->errorMessage = '';
        $this->isLoading = true;

        try {
            $this->validate();

            $user = auth()->user();

            // Verify current password
            if (!Hash::check($this->currentPassword, $user->password)) {
                $this->errorMessage = '❌ Current password is incorrect.';
                $this->isLoading = false;
                return;
            }

            // Check password history (prevent reuse of last 5 passwords)
            if ($this->passwordPolicyService->wasPasswordUsedRecently($user, $this->newPassword)) {
                $this->errorMessage = '❌ This password was used recently. Choose a different password.';
                $this->isLoading = false;
                return;
            }

            // Update password
            $user->password = Hash::make($this->newPassword);
            $user->password_changed_at = now();
            $user->password_reset_required_at = null; // Clear force reset flag
            $user->save();

            // Record in password history
            $this->passwordPolicyService->recordPasswordChange($user, $this->newPassword);

            // Clear session flags
            session()->forget(['password_expired', 'force_password_change', 'password_expiring_soon']);

            // Dispatch success event
            $this->dispatch('notify', type: 'success', message: '✅ Password changed successfully!');

            // Redirect using redirect view
            session()->flash('success', '✅ Password updated successfully. You can now access your dashboard.');
            $this->redirect(route('dashboard'));

        } catch (\Exception $e) {
            $this->errorMessage = "❌ Error: {$e->getMessage()}";
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Toggle password visibility
     */
    public function togglePasswordVisibility(): void
    {
        $this->showPassword = !$this->showPassword;
    }

    /**
     * Toggle current password visibility
     */
    public function toggleCurrentPasswordVisibility(): void
    {
        $this->showCurrentPassword = !$this->showCurrentPassword;
    }

    /**
     * Validate password requirements on input
     */
    public function updatedNewPassword(): void
    {
        $password = $this->newPassword;

        // Clear any previous validation errors for this field
        $this->validateOnly('newPassword');
    }
}
