<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Shared\Rules\Password as PasswordRule;
use Modules\User\Models\AccountToken;
use Modules\User\Services\Contracts\AccountProvisioningService;

/**
 * Two-step account claim flow for provisioned accounts.
 *
 * Step 1 — enter username + activation code.
 * Step 2 — set a new personal password.
 *
 * Intentionally keeps both steps in one component to avoid leaking
 * partial state (e.g. a valid username) to a second URL.
 *
 * Security properties:
 * - Rate-limited on identifier to prevent code enumeration.
 * - No session state is stored between steps except a locked token ID,
 *   so tampering with the ID server-side would simply fail the DB lookup.
 * - The plaintext code is never reflected back to the client after verify.
 */
class ClaimAccount extends Component
{
    // ─── Step 1 ─────────────────────────────────────────────────────────────────

    public string $username = '';
    public string $activation_code = '';

    // ─── Step 2 ─────────────────────────────────────────────────────────────────

    public string $password = '';
    public string $password_confirmation = '';

    // ─── Internal ────────────────────────────────────────────────────────────────

    /**
     * The verified token ID (locked — cannot be tampered with from the client).
     * Only populated after step 1 succeeds.
     */
    #[Locked]
    public ?string $verifiedTokenId = null;

    /**
     * Current step: 1 = enter credentials, 2 = set password.
     */
    #[Locked]
    public int $step = 1;

    // ─── Validation ──────────────────────────────────────────────────────────────

    protected function stepOneRules(): array
    {
        return [
            'username'        => ['required', 'string', 'max:255'],
            'activation_code' => ['required', 'string', 'min:6'],
        ];
    }

    protected function stepTwoRules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', PasswordRule::auto()],
        ];
    }

    // ─── Actions ─────────────────────────────────────────────────────────────────

    /**
     * Verify the activation code and advance to the password step.
     */
    public function verify(AccountProvisioningService $provisioning): void
    {
        $this->validate($this->stepOneRules());

        // Rate-limit by username to prevent brute-force code enumeration.
        $key = 'claim-account:' . Str::lower($this->username);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            // [S2 - Sustain] Audit Log for Brute Force Attempt
            activity('security')
                ->event('claim_account_throttled')
                ->withProperties(['ip' => request()->ip(), 'username' => $this->username])
                ->log('Account claim rate limit exceeded.');

            $this->addError('activation_code', __('auth::claim.throttled'));
            return;
        }

        $plainCode = Str::upper(str_replace([' ', '-'], '-', $this->activation_code));
        $token = $provisioning->findActiveToken($this->username, $plainCode);

        if (! $token) {
            RateLimiter::hit($key, 300); // 5 min decay
            
            // [S2 - Sustain] Audit Log for Failed Attempt
            activity('security')
                ->event('claim_account_failed')
                ->withProperties(['ip' => request()->ip(), 'username' => $this->username])
                ->log('Failed account claim attempt: Invalid code or username.');

            $this->addError('activation_code', __('auth::claim.invalid_code'));
            return;
        }

        RateLimiter::clear($key);

        $this->verifiedTokenId = $token->id;
        $this->step = 2;

        // Clear sensitive input from component state
        $this->activation_code = '';
    }

    /**
     * Complete the claim by setting the user's personal password.
     */
    public function claim(AccountProvisioningService $provisioning): void
    {
        $this->validate($this->stepTwoRules());

        $token = AccountToken::find($this->verifiedTokenId);

        if (! $token || ! $token->isActive()) {
            // Token was consumed or expired between steps (edge case)
            $this->reset();
            $this->step = 1;
            $this->addError('activation_code', __('auth::claim.token_expired'));
            return;
        }

        $user = $token->user;

        $provisioning->claim(
            $token,
            $this->password,
            request()->ip(),
        );

        // [S2 - Sustain] Audit Log for Success
        activity('security')
            ->event('claim_account_success')
            ->withProperties(['ip' => request()->ip(), 'user_id' => $user->id, 'username' => $user->username])
            ->log('Account successfully claimed and activated.');

        flash()->success(__('auth::claim.success'));

        $this->redirect(route('login'), navigate: true);
    }

    // ─── Rendering ───────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('auth::livewire.claim-account')
            ->layout('auth::components.layouts.auth', [
                'title' => __('auth::claim.page_title') . ' | ' . setting('site_title', 'Internara'),
            ]);
    }
}
