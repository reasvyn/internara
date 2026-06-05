<?php

declare(strict_types=1);

namespace App\User\Password\Livewire;

use App\User\Password\Actions\SendPasswordResetLinkAction;
use App\User\Password\Livewire\Forms\ForgotPasswordForm;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ForgotPassword extends Component
{
    public ForgotPasswordForm $form;

    public bool $linkSent = false;

    public function sendResetLink(SendPasswordResetLinkAction $action): void
    {
        $this->form->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('form.email', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        $action->execute($this->form->email);

        RateLimiter::hit($throttleKey, 3600);

        $this->linkSent = true;
        $this->form->reset('email');

        flash()->success(__('passwords.sent'));
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(
            'forgot-password|'.Str::lower($this->form->email).'|'.request()->ip(),
        );
    }

    #[Layout('user::layouts.auth', ['title' => 'Forgot Password'])]
    public function render(): View
    {
        return view('user.password.forgot-password');
    }
}
