<?php

declare(strict_types=1);

namespace Modules\Auth\Registration\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Auth\Services\Contracts\RedirectService;
use Modules\Exception\AppException;
use Modules\Shared\Rules\Password;

class Register extends Component
{
    protected AuthService $authService;

    protected RedirectService $redirectService;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $captcha_token = '';

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'confirmed', Password::auto()],
            'captcha_token' => ['required', new \Modules\Shared\Rules\Turnstile],
        ];
    }

    public function boot(AuthService $authService, RedirectService $redirectService): void
    {
        $this->authService = $authService;
        $this->redirectService = $redirectService;
    }

    public function register(): void
    {
        $validated = $this->validate();

        try {
            // Register user and trigger email verification
            $user = $this->authService->register($validated, sendEmailVerification: true);

            notify(__('auth::ui.register.welcome', [
                'app' => setting('app_name', 'Internara'),
                'name' => $user->name,
            ]), 'success');

            $this->authService->login([
                'email' => $user->email,
                'password' => $validated['password'],
            ]);

            $this->redirect($this->redirectService->getTargetUrl($user), navigate: true);
        } catch (AppException $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('auth::livewire.register')->layout('auth::components.layouts.auth', [
            'title' => __('Daftar Akun | :site_title', [
                'site_title' => setting('site_title', 'Internara'),
            ]),
        ]);
    }
}
