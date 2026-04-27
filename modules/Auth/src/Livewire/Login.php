<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Auth\Services\Contracts\RedirectService;
use Modules\Exception\AppException;

/**
 * Livewire component for handling user login.
 *
 * This component provides the interface and logic for users to log in to the application.
 * It delegates authentication logic to the `AuthService`.
 */
class Login extends Component
{
    protected AuthService $authService;

    protected RedirectService $redirectService;

    public string $identifier = '';

    /**
     * The user's password for login.
     */
    public string $password = '';

    /**
     * Indicates whether the user should be remembered.
     */
    public bool $remember = false;

    /**
     * Define the validation rules for the component properties.
     *
     * @return array<string, array|string>
     */
    protected function rules(): array
    {
        $rules = [
            'identifier' => [
                'required',
                'string',
                $this->isEmail($this->identifier) ? 'email' : null,
            ],
            'password' => 'required|string',
        ];

        return $rules;
    }

    /**
     * Check if the given identifier string is likely an email address.
     */
    protected function isEmail(string $value): bool
    {
        return str_contains($value, '@');
    }

    /**
     * Initializes the component with the AuthService and RedirectService.
     */
    public function boot(AuthService $authService, RedirectService $redirectService): void
    {
        $this->authService = $authService;
        $this->redirectService = $redirectService;
    }

    /**
     * Handles the login attempt.
     *
     * Validates the credentials, attempts to log in the user via AuthService,
     * and redirects to the appropriate dashboard on success.
     */
    public function login(): void
    {
        $this->validate();

        // [S1 - Secure] Brute Force Protection (Rate Limiting)
        $throttleKey = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError(
                'identifier',
                __('auth::ui.login.form.rate_limited', ['seconds' => $seconds]),
            );

            // [S2 - Sustain] Audit Log for Brute Force Attempt
            activity('security')
                ->event('brute_force_lockout')
                ->withProperties(['ip' => request()->ip(), 'identifier' => $this->identifier])
                ->log('Login rate limit exceeded.');

            return;
        }

        try {
            $user = $this->authService->login(
                [
                    'identifier' => $this->identifier,
                    'password' => $this->password,
                ],
                $this->remember,
            );

            // [S1 - Secure] Session Fixation Protection
            session()->regenerate();

            // Clear the rate limiter on success
            RateLimiter::clear($throttleKey);

            flash()->success(__('auth::ui.login.welcome_back', ['name' => $user->name]));

            $this->redirect($this->redirectService->getTargetUrl($user), navigate: true);
        } catch (AppException $e) {
            RateLimiter::hit($throttleKey, 60); // Lock for 60 seconds after 5 attempts
            $this->addError('identifier', $e->getUserMessage());
        }
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->identifier) . '|' . request()->ip());
    }

    /**
     * Renders the login view.
     */
    public function render(): View
    {
        return view('auth::livewire.login')->layout('auth::components.layouts.auth', [
            'title' => __('auth::ui.login.page_title', [
                'site_title' => setting('site_title', 'Internara'),
            ]),
        ]);
    }
}
