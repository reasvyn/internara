<?php

declare(strict_types=1);

namespace App\Auth\Password\Livewire;

use App\Auth\Password\Actions\ResetPasswordAction;
use App\Auth\Password\Livewire\Forms\ResetPasswordForm;
use App\Core\Support\SmartLogger;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

class ResetPassword extends Component
{
    public ResetPasswordForm $form;

    public function mount(string $token): void
    {
        $this->form->token = $token;
    }

    public function resetPassword(ResetPasswordAction $action): void
    {
        $this->form->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('form.password', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        try {
            $action->execute(
                email: $this->form->email,
                token: $this->form->token,
                password: $this->form->password,
                passwordConfirmation: $this->form->password_confirmation,
            );

            RateLimiter::clear($throttleKey);

            flash()->success(__('passwords.reset'));

            $this->redirectRoute('login', navigate: true);
        } catch (RuntimeException $e) {
            RateLimiter::hit($throttleKey, 300);
            $this->addError('form.email', $e->getMessage());

            SmartLogger::error('password_reset_error')
                ->event('password_reset_error')
                ->module('Auth')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
        }
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(
            'reset-password|'.Str::lower($this->form->email).'|'.request()->ip(),
        );
    }

    #[Layout('auth::layouts.auth', ['title' => 'Reset Password'])]
    public function render(): View
    {
        return view('auth.password.reset-password');
    }
}
