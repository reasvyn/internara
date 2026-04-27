<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Shared\Rules\Password;

class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected AuthService $authService;

    public function boot(AuthService $authService): void
    {
        $this->authService = $authService;
    }

    public function mount(Request $request, string $token): void
    {
        $this->token = $token;
        $this->email = (string) $request->query('email', '');
    }

    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'string', 'confirmed', Password::auto()],
        ];
    }

    /**
     * Handles the password reset.
     */
    public function resetPassword(): void
    {
        $this->validate();

        // [S1 - Secure] Brute Force Protection (Rate Limiting)
        $throttleKey = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError(
                'password',
                __('auth::ui.reset_password.form.rate_limited', ['seconds' => $seconds]),
            );

            return;
        }

        $success = $this->authService->resetPassword([
            'token' => $this->token,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        if ($success) {
            // [S2 - Sustain] Audit Log
            activity('security')
                ->event('password_reset_success')
                ->withProperties(['ip' => request()->ip(), 'email' => $this->email])
                ->log('Password has been successfully reset.');

            RateLimiter::clear($throttleKey);

            flash()->success(__('auth::ui.reset_password.success'));

            $this->redirect(route('login'), navigate: true);
        } else {
            RateLimiter::hit($throttleKey, 300); // 5 minute lock

            // [S2 - Sustain] Audit Log
            activity('security')
                ->event('password_reset_failed')
                ->withProperties(['ip' => request()->ip(), 'email' => $this->email])
                ->log('Failed password reset attempt.');

            $this->addError('email', trans('passwords.token'));
        }
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(
            'reset-password|' . Str::lower($this->email) . '|' . request()->ip(),
        );
    }

    public function render(): View
    {
        return view('auth::livewire.reset-password')->layout('auth::components.layouts.auth', [
            'title' =>
                __('auth::ui.reset_password.title') . ' | ' . setting('site_title', 'Internara'),
        ]);
    }
}
