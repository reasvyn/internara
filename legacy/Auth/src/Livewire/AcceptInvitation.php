<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Shared\Rules\Password as PasswordRule;
use Modules\User\Models\AccountToken;
use Modules\User\Services\Contracts\AccountProvisioningService;

/**
 * Accept an admin account invitation via email link.
 *
 * Unlike the activation code flow (ClaimAccount), this component receives
 * the token from the URL, hashes it once in mount(), and revalidates
 * atomically in the claim step.
 *
 * Security properties:
 * - Token is never stored in component state; only the DB record ID is kept.
 * - Token ID is Locked to prevent client-side tampering.
 * - Rate-limited by IP to prevent brute-force against the hash.
 * - Email is marked verified on successful acceptance (inbox proof).
 * - Final claim uses a DB transaction with lockForUpdate() for atomicity.
 */
class AcceptInvitation extends Component
{
    // ─── State ───────────────────────────────────────────────────────────────────

    public string $password = '';

    public string $password_confirmation = '';

    /** Displayed to the user once the token is validated in mount(). */
    public string $userName = '';

    /** Whether the token in the URL was invalid or expired. */
    public bool $invalidToken = false;

    /**
     * The verified AccountToken primary key (locked against tampering).
     */
    #[Locked]
    public ?string $tokenId = null;

    // ─── Lifecycle ───────────────────────────────────────────────────────────────

    public function mount(string $token, AccountProvisioningService $provisioning): void
    {
        // Rate-limit by IP to slow down any token enumeration attempts
        $ipKey = 'invitation-mount:'.(request()->ip() ?? 'unknown');

        if (RateLimiter::tooManyAttempts($ipKey, 20)) {
            $this->invalidToken = true;

            return;
        }

        RateLimiter::hit($ipKey, 300); // 5 min decay

        $record = $provisioning->findActiveInvitationToken($token);

        if (! $record) {
            $this->invalidToken = true;

            return;
        }

        $this->tokenId = $record->id;
        $this->userName = $record->user->name;
    }

    // ─── Actions ─────────────────────────────────────────────────────────────────

    /**
     * Validate token and set password to complete account setup.
     */
    public function accept(AccountProvisioningService $provisioning): void
    {
        $this->validate([
            'password' => ['required', 'string', 'confirmed', PasswordRule::auto()],
        ]);

        // Re-fetch and lock the token atomically to prevent double-claim race
        $token = AccountToken::where('id', $this->tokenId)
            ->where('type', AccountToken::TYPE_INVITATION)
            ->active()
            ->lockForUpdate()
            ->first();

        if (! $token) {
            // Token was consumed or expired between mount and submit
            $this->invalidToken = true;
            $this->addError('password', __('auth::invitation.token_expired'));

            return;
        }

        $provisioning->claim($token, $this->password, request()->ip());

        flash()->success(__('auth::invitation.success'));

        $this->redirect(route('login'), navigate: true);
    }

    // ─── Rendering ───────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('auth::livewire.accept-invitation')->layout('auth::components.layouts.auth', [
            'title' => __('auth::invitation.page_title').' | '.setting('site_title', 'Internara'),
        ]);
    }
}
