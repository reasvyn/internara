<?php

declare(strict_types=1);

namespace Modules\User\Services\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for provisioning and claiming single-use account activation codes.
 *
 * This service separates "account creation by admin" from "credential
 * activation by the end user", ensuring admins never know the final password.
 */
interface AccountProvisioningService
{
    /**
     * Standard token types.
     */
    public const TYPE_ACTIVATION = 'activation';

    public const TYPE_CREDENTIAL_RESET = 'credential_reset';

    /**
     * Generate a long-form invitation token for a privileged account (Admin).
     *
     * Unlike short activation codes, invitation tokens are 64-char hex strings
     * delivered via email link. They are stored as HMAC-SHA256 hashes and
     * looked up by re-hashing the raw URL token, not by scanning.
     *
     * Sends an AdminInvitationNotification email to the user.
     *
     * @param Authenticatable&Model $user The account to invite.
     * @param int $expiresInDays Number of days before link expires.
     * @param (Authenticatable&Model)|null $issuedBy Admin who triggered the invitation.
     *
     * @return string The PLAINTEXT 64-char hex token (used in URL once).
     */
    public function invite(
        Authenticatable&Model $user,
        int $expiresInDays = 7,
        (Authenticatable&Model)|null $issuedBy = null,
    ): string;

    /**
     * Find an active invitation token by its plain hex value.
     *
     * Hashes the plain token via HMAC-SHA256 and queries directly —
     * no linear scan over all tokens.
     */
    public function findActiveInvitationToken(string $plainToken): ?Model;

    /**
     * Generate a new activation code for the given user.
     *
     * Invalidates any previous active tokens of the same type.
     * Returns the PLAINTEXT code — it must be presented to the issuing admin
     * exactly once and then distributed through institution-controlled channels.
     *
     * @param Authenticatable&Model $user The account to provision.
     * @param string $type self::TYPE_ACTIVATION | self::TYPE_CREDENTIAL_RESET
     * @param int $expiresInDays Number of days before the code expires (0 = no expiry).
     * @param (Authenticatable&Model)|null $issuedBy Admin who triggered the provisioning.
     *
     * @return string The plaintext activation code.
     */
    public function provision(
        Authenticatable&Model $user,
        string $type = self::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        (Authenticatable&Model)|null $issuedBy = null,
    ): string;

    /**
     * Invalidate existing active tokens for the user+type and generate a fresh one.
     * Convenience wrapper around provision() for the "reissue" UX action.
     */
    public function reissue(
        Authenticatable&Model $user,
        string $type = self::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        (Authenticatable&Model)|null $issuedBy = null,
    ): string;

    /**
     * Find the active token for a given username and verify the plain code.
     *
     * Returns the AccountToken if the code is valid and not expired/claimed,
     * or null if the lookup or verification fails.
     * Intentionally does not leak whether the username or code was the wrong part.
     */
    public function findActiveToken(string $username, string $plainCode): ?Model;

    /**
     * Complete the claim: set the user's password, mark the token as consumed,
     * clear setup_required, and optionally activate the account.
     *
     * setup_required was set to true when the token was provisioned.
     * After a successful claim it is cleared, signalling that all initial
     * setup steps have been completed.
     *
     * @param Model $token A token returned by findActiveToken().
     * @param string $newPassword The user's self-chosen plaintext password.
     * @param string|null $ipAddress Claimant's IP for audit log.
     */
    public function claim(Model $token, string $newPassword, ?string $ipAddress = null): void;

    /**
     * Generate credential slips data for a set of users.
     *
     * Returns an array of ['user' => User, 'plain_code' => string] pairs.
     * Each code is freshly provisioned and the plaintext is only available
     * in the returned array — it is never retrievable from the database again.
     *
     * @param iterable<Authenticatable&Model> $users
     * @param (Authenticatable&Model)|null $issuedBy
     *
     * @return array<array{user: Authenticatable&Model, plain_code: string}>
     */
    public function provisionBatch(
        iterable $users,
        string $type = self::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        (Authenticatable&Model)|null $issuedBy = null,
    ): array;
}
