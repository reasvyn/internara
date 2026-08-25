<?php

declare(strict_types=1);

namespace App\Auth\Login\Livewire;

use App\Auth\Login\Actions\LoginAction;
use App\Auth\Login\Data\LoginData;
use App\Auth\Login\Livewire\Forms\LoginForm;
use App\Core\Exceptions\RejectedException;
use App\Core\Services\SmartLogger;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;
use Throwable;

class Login extends Component
{
    use Interactions;

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
            $user = $loginAction->execute(new LoginData(
                identifier: $this->form->identifier,
                password: $this->form->password,
                remember: $this->form->remember,
            ));

            RateLimiter::clear($throttleKey);

            $this->toast()->success(__('auth.login.welcome_back', ['name' => $user->name]))->send();

            $this->redirect($this->getIntendedUrl(), navigate: true);
        } catch (RejectedException $e) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('form.identifier', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            $this->addError('form.identifier', __('auth.failed'));

            SmartLogger::error('Unexpected error during login')
                ->module('Auth')
                ->event('login.error')
                ->withPayload(['error' => $e->getMessage()])
                ->withPiiMasking()
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
