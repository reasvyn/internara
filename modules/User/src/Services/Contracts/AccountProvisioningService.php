<?php

declare(strict_types=1);

namespace Modules\User\Services\Contracts;

use Modules\User\Models\AccountToken;
use Modules\User\Models\User;

/**
 * Contract for provisioning and claiming single-use account activation codes.
 *
 * This service separates "account creation by admin" from "credential
 * activation by the end user", ensuring admins never know the final password.
 */
interface AccountProvisioningService
{
    /**
     * Generate a new activation code for the given user.
     *
     * Invalidates any previous active tokens of the same type.
     * Returns the PLAINTEXT code — it must be presented to the issuing admin
     * exactly once and then distributed through institution-controlled channels.
     *
     * @param  User   $user          The account to provision.
     * @param  string $type          AccountToken::TYPE_ACTIVATION | TYPE_CREDENTIAL_RESET
     * @param  int    $expiresInDays Number of days before the code expires (0 = no expiry).
     * @param  User|null $issuedBy   Admin who triggered the provisioning.
     * @return string                The plaintext activation code.
     */
    public function provision(
        User $user,
        string $type = AccountToken::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        ?User $issuedBy = null,
    ): string;

    /**
     * Invalidate existing active tokens for the user+type and generate a fresh one.
     * Convenience wrapper around provision() for the "reissue" UX action.
     */
    public function reissue(
        User $user,
        string $type = AccountToken::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        ?User $issuedBy = null,
    ): string;

    /**
     * Find the active token for a given username and verify the plain code.
     *
     * Returns the AccountToken if the code is valid and not expired/claimed,
     * or null if the lookup or verification fails.
     * Intentionally does not leak whether the username or code was the wrong part.
     */
    public function findActiveToken(string $username, string $plainCode): ?AccountToken;

    /**
     * Complete the claim: set the user's password, mark the token as consumed,
     * clear setup_required, and optionally activate the account.
     *
     * setup_required was set to true when the token was provisioned.
     * After a successful claim it is cleared, signalling that all initial
     * setup steps have been completed.
     *
     * @param  AccountToken $token       A token returned by findActiveToken().
     * @param  string       $newPassword The user's self-chosen plaintext password.
     * @param  string|null  $ipAddress   Claimant's IP for audit log.
     */
    public function claim(AccountToken $token, string $newPassword, ?string $ipAddress = null): void;

    /**
     * Generate credential slips data for a set of users.
     *
     * Returns an array of ['user' => User, 'plain_code' => string] pairs.
     * Each code is freshly provisioned and the plaintext is only available
     * in the returned array — it is never retrievable from the database again.
     *
     * @param  iterable<User> $users
     * @param  string         $type
     * @param  int            $expiresInDays
     * @param  User|null      $issuedBy
     * @return array<array{user: User, plain_code: string}>
     */
    public function provisionBatch(
        iterable $users,
        string $type = AccountToken::TYPE_ACTIVATION,
        int $expiresInDays = 30,
        ?User $issuedBy = null,
    ): array;
}
