<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;

class ForgotPassword extends Component
{
    public string $email = '';

    protected AuthService $authService;

    public function boot(AuthService $authService): void
    {
        $this->authService = $authService;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    public function sendResetLink(): void
    {
        $this->validate();

        if (is_development()) {
            $user = \Modules\User\Models\User::where('email', $this->email)->first();

            if ($user) {
                // Log masked email for flow verification
                $maskedEmail = \Modules\Shared\Support\Masker::email($this->email);
            if (is_development()) {
                \Illuminate\Support\Facades\Log::info("Development: Password reset initiated for {$maskedEmail}. Redirecting to shortcut.");
            }

                $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);

                $this->redirect(
                    route('password.reset', [
                        'token' => $token,
                        'email' => $this->email,
                    ]),
                    navigate: true
                );

                return;
            }
        }

        $this->authService->sendPasswordResetLink($this->email);

        flash()->success(__('auth::ui.forgot_password.sent'));

        $this->reset('email');
    }

    public function render(): View
    {
        return view('auth::livewire.forgot-password')->layout('auth::components.layouts.auth', [
            'title' => __('auth::ui.forgot_password.title') . ' | ' . setting('site_title', 'Internara'),
        ]);
    }
}
