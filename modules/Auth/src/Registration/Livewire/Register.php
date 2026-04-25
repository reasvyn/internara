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
        $rules = [
            'name' => 'required|string|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'confirmed', Password::auto()],
        ];

        if (config('services.cloudflare.turnstile.site_key')) {
            $rules['captcha_token'] = ['required', new \Modules\Shared\Rules\Turnstile];
        }

        return $rules;
    }

    public function boot(AuthService $authService, RedirectService $redirectService): void
    {
        $this->authService = $authService;
        $this->redirectService = $redirectService;
    }

    public function register(): void
    {
        $validated = $this->validate();

        // [S1 - Secure] Brute Force / Spam Protection (Rate Limiting)
        $throttleKey = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($throttleKey, 2)) { // 2 attempts per hour per IP
            $this->addError('email', __('auth::ui.register.form.rate_limited'));
            
            return;
        }

        try {
            // [S1 - Secure] Explicit Role Lockdown to STUDENT
            $user = $this->authService->register(
                $validated, 
                roles: [Role::STUDENT->value], 
                sendEmailVerification: true
            );

            // [S2 - Sustain] Audit Log
            activity('security')
                ->event('registration_success')
                ->withProperties(['ip' => request()->ip(), 'email' => $user->email, 'role' => Role::STUDENT->value])
                ->log('New student account registered.');

            RateLimiter::hit($throttleKey, 3600); // 1 hour lock

            flash()->success(
                __('auth::ui.register.welcome', [
                    'app' => setting('app_name', 'Internara'),
                    'name' => $user->name,
                ]),
            );

            $this->authService->login([
                'email' => $user->email,
                'password' => $validated['password'],
            ]);

            $this->redirect($this->redirectService->getTargetUrl($user), navigate: true);
        } catch (AppException $e) {
            // [S2 - Sustain] Audit Log for Failure
            activity('security')
                ->event('registration_failed')
                ->withProperties(['ip' => request()->ip(), 'email' => $this->email])
                ->log('Registration attempt failed: ' . $e->getMessage());

            $this->addError('email', $e->getUserMessage());
        }
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate('registration|'.request()->ip());
    }

    public function render(): View
    {
        return view('auth::livewire.register')->layout('auth::components.layouts.auth', [
            'title' => __('auth::ui.register.page_title', [
                'site_title' => setting('site_title', 'Internara'),
            ]),
        ]);
    }
}
