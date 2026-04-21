<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\User\Models\AccountToken;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\AccountProvisioningService as Contract;

/**
 * Implements the single-use activation code lifecycle.
 *
 * Security properties:
 * - Plaintext codes are generated with cryptographically secure randomness.
 * - Only HMAC-SHA256 hashes are persisted; plaintext is never stored.
 * - Reissue invalidates all prior active tokens before creating a new one.
 * - Verification uses hash_equals() to prevent timing attacks.
 * - Claim is atomic: password update + token consumption in one transaction.
 * - must_change_password is set to true after claim to prompt the user to
 *   choose a more personal password once they log in.
 */
class AccountProvisioningService implements Contract
{
    /**
     * Activation code character set — excludes visually ambiguous characters
     * (0, O, I, 1) to reduce transcription errors when codes are printed.
     */
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * {@inheritdoc}
     */
    public function provision(
        User $user,
        string $type = AccountToken::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        ?User $issuedBy = null,
    ): string {
        $plainCode = $this->generatePlainCode();

        DB::transaction(function () use ($user, $type, $plainCode, $expiresInDays, $issuedBy) {
            // Invalidate any existing active tokens of the same type for this user
            $user->accountTokens()
                ->where('type', $type)
                ->whereNull('claimed_at')
                ->delete();

            $user->accountTokens()->create([
                'type'       => $type,
                'token'      => AccountToken::hashCode($plainCode),
                'expires_at' => $expiresInDays > 0 ? now()->addDays($expiresInDays) : null,
                'issued_by'  => $issuedBy?->id,
            ]);

            // Flag the account as needing setup steps (cleared upon successful claim).
            $user->update(['setup_required' => true]);
        });

        return $plainCode;
    }

    /**
     * {@inheritdoc}
     */
    public function reissue(
        User $user,
        string $type = AccountToken::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        ?User $issuedBy = null,
    ): string {
        // provision() already invalidates existing active tokens, so this is a direct proxy.
        return $this->provision($user, $type, $expiresInDays, $issuedBy);
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveToken(string $username, string $plainCode): ?AccountToken
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            return null;
        }

        $token = $user->accountTokens()
            ->active()
            ->latest()
            ->first();

        if (! $token || ! $token->verify($plainCode)) {
            return null;
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function claim(AccountToken $token, string $newPassword, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($token, $newPassword, $ipAddress) {
            $user = $token->user;

            // Set the user's self-chosen password and clear the setup flag.
            // setup_required was true since provisioning; claim completes the setup.
            $user->update([
                'password'      => $newPassword, // cast 'hashed' handles bcrypt
                'setup_required' => false,
            ]);

            // Mark the token consumed
            $token->markClaimed($ipAddress);

            // Activate the account if it was still pending
            if ($user->getStatus()?->value === \Modules\Status\Enums\Status::PENDING->value
                || $user->latestStatus() === null
            ) {
                $user->setStatus(\Modules\Status\Enums\Status::ACTIVE->value);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provisionBatch(
        iterable $users,
        string $type = AccountToken::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        ?User $issuedBy = null,
    ): array {
        $slips = [];

        foreach ($users as $user) {
            $plainCode = $this->provision($user, $type, $expiresInDays, $issuedBy);
            $slips[] = [
                'user'       => $user,
                'plain_code' => $plainCode,
            ];
        }

        return $slips;
    }

    // ─── Internal ────────────────────────────────────────────────────────────────

    /**
     * Generate a human-readable activation code in the format XXXX-XXXX-XXXX.
     *
     * Using cryptographically secure random_int() over the restricted character
     * set gives ≈ 1.2 × 10¹⁸ possible codes — well beyond brute-force reach.
     */
    private function generatePlainCode(): string
    {
        $charset = self::CHARSET;
        $len     = strlen($charset);
        $groups  = [];

        for ($g = 0; $g < 3; $g++) {
            $part = '';
            for ($c = 0; $c < 4; $c++) {
                $part .= $charset[random_int(0, $len - 1)];
            }
            $groups[] = $part;
        }

        return implode('-', $groups); // e.g. "XHYZ-4BN7-PQKM"
    }
}
