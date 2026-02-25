<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface AuthService
{
    /**
     * Attempts to authenticate a user into the system using provided credentials.
     *
     * This method acts as the primary security gateway, verifying identity
     * while enforcing rate limiting and session protection to prevent
     * brute-force attacks.
     *
     * @param array $credentials Contains 'email' (identifier) and 'password'.
     * @param bool $remember Whether to persist the session via long-lived cookies.
     *
     * @throws \Modules\Exception\AppException If authentication fails or account is locked.
     *
     * @return Authenticatable|User The verified stakeholder entity.
     */
    public function login(array $credentials, bool $remember = false): Authenticatable;

    /**
     * Destroys the current user session and invalidates authentication tokens.
     *
     * Ensures that all session data is flushed to prevent session reuse
     * or unauthorized access from shared devices.
     */
    public function logout(): void;

    /**
     * Registers a new stakeholder into the ecosystem with assigned roles.
     *
     * This method orchestrates the creation of the User entity, assigning
     * initial permissions, and optionally triggering the verification flow
     * to satisfy institution-level security policies.
     *
     * @param array $data Basic identity data (name, email, password).
     * @param string|array|null $roles Standard roles defined in the SSoT.
     * @param bool $sendEmailVerification Flag to initiate the email trust loop.
     *
     * @throws \Modules\Exception\AppException If identity constraints are violated.
     *
     * @return Authenticatable The newly provisioned identity.
     */
    public function register(
        array $data,
        string|array|null $roles = null,
        bool $sendEmailVerification = false,
    ): Authenticatable;

    /**
     * Retrieves the identity of the stakeholder in the current session context.
     *
     * Acts as the internal accessor for authenticated data across all modules,
     * ensuring that the system always operates within a verified user scope.
     */
    public function getAuthenticatedUser(): ?Authenticatable;

    /**
     * Updates the user's secret credential with mandatory verification.
     *
     * Enforces security by requiring the current secret before allowing a
     * transition to a new one, mitigating unauthorized account takeover.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user The identity being updated.
     * @param string $currentPassword Verification of existing ownership.
     * @param string $newPassword The new credential to be hashed.
     *
     * @throws \Modules\Exception\AppException If verification or complexity rules fail.
     */
    public function changePassword(Authenticatable $user, string $currentPassword, string $newPassword): bool;

    /**
     * Initiates the password recovery protocol via secure communication.
     *
     * Generates a time-limited, one-time-use token to allow users to
     * regain access without compromising current security invariants.
     */
    public function sendPasswordResetLink(string $email): void;

    /**
     * Finalizes the password recovery flow using a verified token.
     */
    public function resetPassword(array $credentials): bool;

    /**
     * Certifies the ownership of an email address within the system.
     *
     * Completes the trust loop required for high-frequency notifications
     * and institutional administrative compliance.
     */
    public function verifyEmail(string $id, string $hash): bool;

    /**
     * Resends the verification challenge to the user's registered email.
     *
     * @throws \Modules\Exception\AppException If the trust loop is already complete.
     */
    public function resendVerificationEmail(Authenticatable $user): void;

    /**
     * Performs a one-time secret verification for high-privilege actions.
     *
     * Used as a temporary security elevation (PE) gate before allowing
     * access to sensitive institutional configurations or PII.
     */
    public function confirmPassword(Authenticatable $user, string $password): bool;
}
