<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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

    /**
     * Sends the password reset link.
     */
    public function sendResetLink(): void
    {
        $this->validate();

        // [S1 - Secure] Brute Force Protection (Rate Limiting)
        $throttleKey = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            // 3 attempts per hour
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError(
                'email',
                __('auth::ui.forgot_password.form.rate_limited', ['seconds' => $seconds]),
            );

            return;
        }

        $this->authService->sendPasswordResetLink($this->email);

        // [S2 - Sustain] Audit Log
        activity('security')
            ->event('password_reset_initiated')
            ->withProperties(['ip' => request()->ip(), 'email' => $this->email])
            ->log('Password reset link requested.');

        RateLimiter::hit($throttleKey, 3600); // Lock for 1 hour after 3 attempts

        flash()->success(__('auth::ui.forgot_password.sent'));

        $this->reset('email');
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(
            'forgot-password|'.Str::lower($this->email).'|'.request()->ip(),
        );
    }

    public function render(): View
    {
        return view('auth::livewire.forgot-password')->layout('auth::components.layouts.auth', [
            'title' => __('auth::ui.forgot_password.title').' | '.setting('site_title', 'Internara'),
        ]);
    }
}
