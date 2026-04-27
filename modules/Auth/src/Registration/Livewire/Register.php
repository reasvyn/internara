<?php

declare(strict_types=1);

namespace Modules\Auth\Registration\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Auth\Services\Contracts\RedirectService;
use Modules\Exception\AppException;
use Modules\Permission\Enums\Role;
use Modules\Shared\Services\UsernameGenerator;
use Modules\User\Livewire\Forms\UserForm;

class Register extends Component
{
    /**
     * Standardized User Form Object.
     */
    public UserForm $form;

    protected AuthService $authService;

    protected RedirectService $redirectService;

    /**
     * Initializes the component.
     */
    public function boot(AuthService $authService, RedirectService $redirectService): void
    {
        $this->authService = $authService;
        $this->redirectService = $redirectService;
    }

    /**
     * Mounts the component and sets default student role.
     */
    public function mount(): void
    {
        $this->form->roles = [Role::STUDENT->value];
        $this->form->status = 'active';
    }

    /**
     * Handles the student registration.
     */
    public function register(UsernameGenerator $usernameGenerator): void
    {
        // [S1 - Secure] Brute Force / Spam Protection (Rate Limiting)
        $throttleKey = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($throttleKey, 2)) {
            $this->addError('form.email', __('auth::ui.register.form.rate_limited'));

            return;
        }

        // Standard validation from Form Object
        $this->form->validate();

        try {
            // [S2 - Sustain] Autonomous Username Generation (Standardized std_... pattern)
            if (empty($this->form->username)) {
                $this->form->username = $usernameGenerator->generate(
                    $this->form->email,
                    Role::STUDENT->value,
                );
            }

            // [S1 - Secure] Explicit Role Lockdown
            $this->form->roles = [Role::STUDENT->value];

            // Register user and trigger email verification
            $user = $this->authService->register(
                $this->form->all(),
                roles: $this->form->roles,
                sendEmailVerification: true,
            );

            // [S2 - Sustain] Audit Log
            activity('security')
                ->event('registration_success')
                ->performedOn($user)
                ->withProperties(['ip' => request()->ip(), 'role' => Role::STUDENT->value])
                ->log('New student account registered and identity generated.');

            RateLimiter::hit($throttleKey, 3600);

            flash()->success(
                __('auth::ui.register.welcome', [
                    'app' => setting('app_name', 'Internara'),
                    'name' => $user->name,
                ]),
            );

            // Auto-login after registration
            $this->authService->login([
                'email' => $user->email,
                'password' => $this->form->password,
            ]);

            $this->redirect($this->redirectService->getTargetUrl($user), navigate: true);
        } catch (AppException $e) {
            activity('security')
                ->event('registration_failed')
                ->withProperties(['ip' => request()->ip(), 'email' => $this->form->email])
                ->log('Registration attempt failed: ' . $e->getMessage());

            $this->addError('form.email', $e->getUserMessage());
        }
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate('registration|' . request()->ip());
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('auth::livewire.register')->layout('auth::components.layouts.auth', [
            'title' => __('auth::ui.register.page_title', [
                'site_title' => setting('site_title', 'Internara'),
            ]),
        ]);
    }
}
