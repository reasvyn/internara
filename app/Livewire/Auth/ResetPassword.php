<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Domain\Auth\Actions\ResetPasswordAction;
use App\Domain\Auth\Exceptions\AuthException;
use App\Domain\Auth\Exceptions\AuthExceptionRenderer;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ResetPassword extends Component
{
    public string $token = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(ResetPasswordAction $action): void
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('password', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        try {
            $action->execute(
                email: $this->email,
                token: $this->token,
                password: $this->password,
                passwordConfirmation: $this->password_confirmation,
            );

            RateLimiter::clear($throttleKey);

            flash()->success(__('passwords.reset'));

            $this->redirectRoute('login', navigate: true);
        } catch (AuthException $e) {
            RateLimiter::hit($throttleKey, 300);
            $this->addError('email', $e->getMessage());
            AuthExceptionRenderer::handle($this, $e);
        }
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(
            'reset-password|'.Str::lower($this->email).'|'.request()->ip(),
        );
    }

    /**
     * Render the reset password view.
     */
    #[Layout('layouts::auth', ['title' => 'Reset Password'])]
    public function render(): View
    {
        return view('auth.reset-password');
    }
}
