<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\User\Models\User;

interface AuthService
{
    /**
     * Attempt to log in a user with the given credentials.
     *
     * @param array $credentials Contains 'email' (which can be an email or username), 'password'.
     * @param bool $remember Whether to "remember" the user.
     *
     * @throws \Modules\Exception\AppException If authentication fails.
     *
     * @return Authenticatable|User The authenticated user.
     */
    public function login(array $credentials, bool $remember = false): Authenticatable|User;

    /**
     * Log out the currently authenticated user.
     */
    public function logout(): void;

    /**
     * Register a new user.
     *
     * @param array $data Contains user data including 'name', 'email', 'password'.
     * @param string|array|null $roles Roles to assign to the user upon registration.
     * @param bool $sendEmailVerification Whether to send an email verification notification.
     *
     * @throws \Modules\Exception\AppException If registration fails (e.g., duplicate email).
     *
     * @return User The newly registered user.
     */
    public function register(
        array $data,
        string|array|null $roles = null,
        bool $sendEmailVerification = false,
    ): User;

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|User|null The authenticated user, or null if no user is authenticated.
     */
    public function getAuthenticatedUser(): Authenticatable|User|null;

    /**
     * Change the password for a user.
     *
     * @param \Modules\User\Models\User $user The user whose password is to be changed.
     * @param string $currentPassword The user's current password.
     * @param string $newPassword The new password for the user.
     *
     * @throws \Modules\Exception\AppException If the current password does not match.
     *
     * @return bool True if the password was successfully changed, false otherwise.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool;

    /**
     * Send the password reset link to a user.
     *
     * @param string $email The email address of the user.
     */
    public function sendPasswordResetLink(string $email): void;

    /**
     * Reset the password for a user.
     *
     * @param array $credentials Contains 'token', 'email', 'password', 'password_confirmation'.
     *
     * @return bool True if the password was successfully reset, false otherwise.
     */
    public function resetPassword(array $credentials): bool;

    /**
     * Verify a user's email address.
     *
     * @param string $id The user ID.
     * @param string $hash The email verification hash.
     *
     * @return bool True if the email was successfully verified, false otherwise.
     */
    public function verifyEmail(string $id, string $hash): bool;

    /**
     * Resend the email verification notification.
     *
     * @param \Modules\User\Models\User $user The user to resend the verification email to.
     *
     * @throws \Modules\Exception\AppException If the email is already verified.
     */
    public function resendVerificationEmail(User $user): void;

    /**
     * Confirm a user's password.
     *
     * @param \Modules\User\Models\User $user The user to confirm the password for.
     * @param string $password The password to confirm.
     *
     * @return bool True if the password matches, false otherwise.
     */
    public function confirmPassword(User $user, string $password): bool;
}
