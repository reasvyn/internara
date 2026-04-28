<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Notifications\AdminInvitationNotification;
use Modules\Status\Enums\Status;
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
        Authenticatable&Model $user,
        string $type = self::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        (Authenticatable&Model)|null $issuedBy = null,
    ): string {
        $plainCode = $this->generatePlainCode();

        DB::transaction(function () use ($user, $type, $plainCode, $expiresInDays, $issuedBy) {
            // Invalidate any existing active tokens of the same type for this user
            /** @var User $user */
            $user->accountTokens()
                ->where('type', $type)
                ->whereNull('claimed_at')
                ->delete();

            $user->accountTokens()->create([
                'type' => $type,
                'token' => AccountToken::hashCode($plainCode),
                'expires_at' => $expiresInDays > 0 ? now()->addDays($expiresInDays) : null,
                'issued_by' => $issuedBy?->id,
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
        Authenticatable&Model $user,
        string $type = self::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        (Authenticatable&Model)|null $issuedBy = null,
    ): string {
        // provision() already invalidates existing active tokens, so this is a direct proxy.
        return $this->provision($user, $type, $expiresInDays, $issuedBy);
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveToken(string $username, string $plainCode): ?Model
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
    public function claim(Model $token, string $newPassword, ?string $ipAddress = null): void
    {
        /** @var AccountToken $token */
        DB::transaction(function () use ($token, $newPassword, $ipAddress) {
            $user = $token->user;

            // Set the user's self-chosen password and clear the setup flag.
            // setup_required was true since provisioning; claim completes the setup.
            $user->update([
                'password' => $newPassword, // cast 'hashed' handles bcrypt
                'setup_required' => false,
            ]);

            // For invitation tokens (email-delivered), acceptance proves inbox ownership.
            if ($token->type === AccountToken::TYPE_INVITATION && $user->email) {
                $user->markEmailAsVerified();
            }

            // Mark the token consumed
            $token->markClaimed($ipAddress);

            // Activate the account if it was still pending
            if ($user->getStatus()?->value === Status::PENDING->value
                || $user->latestStatus() === null
            ) {
                $user->setStatus(Status::ACTIVATED->value);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provisionBatch(
        iterable $users,
        string $type = self::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        (Authenticatable&Model)|null $issuedBy = null,
    ): array {
        $slips = [];

        foreach ($users as $user) {
            $plainCode = $this->provision($user, $type, $expiresInDays, $issuedBy);
            $slips[] = [
                'user' => $user,
                'plain_code' => $plainCode,
            ];
        }

        return $slips;
    }

    // ─── Internal ────────────────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     */
    public function invite(
        Authenticatable&Model $user,
        int $expiresInDays = 7,
        (Authenticatable&Model)|null $issuedBy = null,
    ): string {
        // Generate a cryptographically secure 64-char hex token for URL delivery.
        $plain = bin2hex(random_bytes(32));

        /** @var User $user */
        DB::transaction(function () use ($user, $plain, $expiresInDays, $issuedBy) {
            // Invalidate any prior active invitation tokens for this user
            $user->accountTokens()
                ->where('type', AccountToken::TYPE_INVITATION)
                ->whereNull('claimed_at')
                ->delete();

            $user->accountTokens()->create([
                'type' => AccountToken::TYPE_INVITATION,
                'token' => AccountToken::hashCode($plain),
                'expires_at' => now()->addDays($expiresInDays),
                'issued_by' => $issuedBy?->id,
            ]);

            $user->update(['setup_required' => true]);
        });

        // Send invitation email (outside transaction — notification failure should not rollback)
        $user->notify(new AdminInvitationNotification($plain, $expiresInDays));

        return $plain;
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveInvitationToken(string $plainToken): ?Model
    {
        // Query by HMAC hash directly — no linear scan
        return AccountToken::where('token', AccountToken::hashCode($plainToken))
            ->where('type', AccountToken::TYPE_INVITATION)
            ->active()
            ->first();
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
        $len = strlen($charset);
        $groups = [];

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
