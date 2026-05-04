<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Domain\Auth\Actions\LoginAction;
use App\Domain\Auth\Exceptions\AuthException;
use App\Domain\Auth\Exceptions\AuthExceptionRenderer;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|string')]
    public string $identifier = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Attempt to log in the user.
     */
    public function login(LoginAction $loginAction): void
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('identifier', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        try {
            $user = $loginAction->execute(
                identifier: $this->identifier,
                password: $this->password,
                remember: $this->remember,
            );

            session()->regenerate();
            RateLimiter::clear($throttleKey);

            flash()->success(__('auth.login.welcome_back', ['name' => $user->name]));

            $this->redirect($this->getIntendedUrl(), navigate: true);
        } catch (AuthException $e) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('identifier', $e->getMessage());
            AuthExceptionRenderer::handle($this, $e);
        }
    }

    /**
     * Get the intended URL from the session or default to dashboard.
     */
    protected function getIntendedUrl(): string
    {
        return session()->pull('url.intended', '/dashboard');
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->identifier).'|'.request()->ip());
    }

    /**
     * Render the login view.
     */
    #[Layout('layouts::auth', ['title' => 'Login'])]
    public function render(): View
    {
        return view('auth.login');
    }
}
