<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire;

use App\Domain\Auth\Actions\ResetPasswordAction;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

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
        } catch (RuntimeException $e) {
            RateLimiter::hit($throttleKey, 300);
            $this->addError('email', $e->getMessage());

            SmartLogger::error('Password reset error')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
        }
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(
            'reset-password|'.Str::lower($this->email).'|'.request()->ip(),
        );
    }

    #[Layout('auth::layouts.auth', ['title' => 'Reset Password'])]
    public function render(): View
    {
        return view('auth.reset-password');
    }
}
