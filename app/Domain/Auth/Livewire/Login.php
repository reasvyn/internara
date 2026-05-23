<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire;

use App\Domain\Auth\Actions\LoginAction;
use App\Domain\Auth\Livewire\Forms\LoginForm;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

class Login extends Component
{
    public LoginForm $form;

    public function login(LoginAction $loginAction): void
    {
        $this->form->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('form.identifier', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        try {
            $user = $loginAction->execute(
                identifier: $this->form->identifier,
                password: $this->form->password,
                remember: $this->form->remember,
            );

            session()->regenerate();
            RateLimiter::clear($throttleKey);

            flash()->success(__('auth.login.welcome_back', ['name' => $user->name]));

            $this->redirect($this->getIntendedUrl(), navigate: true);
        } catch (RuntimeException $e) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('form.identifier', $e->getMessage());

            SmartLogger::error('Login error')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
        }
    }

    protected function getIntendedUrl(): string
    {
        return session()->pull('url.intended', '/dashboard');
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->form->identifier).'|'.request()->ip());
    }

    #[Layout('auth::layouts.auth', ['title' => 'Login'])]
    public function render(): View
    {
        return view('auth.login');
    }
}
