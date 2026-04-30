<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\Auth\SendPasswordResetLinkAction;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ForgotPassword extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    public bool $linkSent = false;

    /**
     * Send the password reset link.
     */
    public function sendResetLink(SendPasswordResetLinkAction $action): void
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        $action->execute($this->email);

        RateLimiter::hit($throttleKey, 3600);

        $this->linkSent = true;
        $this->reset('email');

        flash()->success(__('passwords.sent'));
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate('forgot-password|' . Str::lower($this->email) . '|' . request()->ip());
    }

    /**
     * Render the forgot password view.
     */
    #[Layout('components.layouts.auth', ['title' => 'Forgot Password'])]
    public function render(): View
    {
        return view('auth.forgot-password');
    }
}
