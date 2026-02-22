<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Auth\Services\Contracts\AuthService as AuthServiceContract;
use Modules\Exception\AppException;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service to manage user authentication, registration, password management, and email verification.
 */
class AuthService implements AuthServiceContract
{
    /**
     * Create a new AuthService instance.
     */
    public function __construct(protected UserService $userService) {}

    /**
     * Attempt to log in a user with the given credentials.
     *
     * @param array $credentials Contains 'email' (which can be an email or username), 'password'.
     * @param bool $remember Whether to "remember" the user.
     *
     * @throws AppException If authentication fails.
     *
     * @return Authenticatable|User The authenticated user.
     */
    public function login(array $credentials, bool $remember = false): Authenticatable|User
    {
        // The 'identifier' field from the form can be either an email or a username.
        $identifier =
            $credentials['identifier'] ??
            ($credentials['email'] ?? ($credentials['username'] ?? ''));

        // Determine if the identifier is an email or a username.
        $loginField = Str::contains($identifier, '@') ? 'email' : 'username';

        $authCredentials = [
            $loginField => $identifier,
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($authCredentials, $remember)) {
            // Mask identifier for logging
            $maskedIdentifier = Str::contains($identifier, '@')
                ? \Modules\Shared\Support\Masker::email($identifier)
                : \Modules\Shared\Support\Masker::sensitive($identifier);

            throw new AppException(
                userMessage: 'auth::exceptions.invalid_credentials',
                logMessage: 'Authentication attempt failed for: '.$maskedIdentifier,
                code: Response::HTTP_UNAUTHORIZED,
            );
        }

        return Auth::user();
    }

    /**
     * Log out the currently authenticated user.
     */
    public function logout(): void
    {
        Auth::logout();
    }

    public function register(
        array $data,
        string|array|null $roles = null,
        bool $sendEmailVerification = false,
    ): User {
        // Prevent role escalation by filtering roles from user input
        $sanitizedData = \Illuminate\Support\Arr::except($data, ['roles', 'role']);

        $user = $this->userService->create(
            array_merge($sanitizedData, [
                'roles' => $roles,
            ]),
        );

        if ($sendEmailVerification) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|User|null The authenticated user, or null if no user is authenticated.
     */
    public function getAuthenticatedUser(): Authenticatable|User|null
    {
        return Auth::user();
    }

    /**
     * Change the password for a user.
     *
     * @param \Modules\User\Models\User $user The user whose password is to be changed.
     * @param string $currentPassword The user's current password.
     * @param string $newPassword The new password for the user.
     *
     * @throws AppException If the current password does not match.
     *
     * @return bool True if the password was successfully changed, false otherwise.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new AppException(
                userMessage: 'auth::exceptions.password_mismatch',
                code: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Send the password reset link to a user.
     *
     * @param string $email The email address to send the reset link to.
     */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * Reset the password for a user.
     *
     * @param array $credentials Contains 'token', 'email', 'password', 'password_confirmation'.
     *
     * @return bool True if the password was successfully reset, false otherwise.
     */
    public function resetPassword(array $credentials): bool
    {
        $response = Password::reset($credentials, function (User $user, string $password) {
            $user->password = Hash::make($password);
            $user->save();
        });

        return $response === Password::PASSWORD_RESET;
    }

    /**
     * Verify a user's email address.
     *
     * @param string $id The user ID.
     * @param string $hash The email verification hash.
     *
     * @return bool True if the email was successfully verified, false otherwise.
     */
    public function verifyEmail(string $id, string $hash): bool
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return false;
        }

        if ($user->hasVerifiedEmail()) {
            return true;
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            return true;
        }

        return false;
    }

    /**
     * Resend the email verification notification.
     *
     * @param \Modules\User\Models\User $user The user to resend the verification email to.
     *
     * @throws AppException If the email is already verified.
     */
    public function resendVerificationEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new AppException(
                userMessage: 'auth::exceptions.email_already_verified',
                code: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user->sendEmailVerificationNotification();
    }

    /**
     * Confirm a user's password.
     *
     * @param \Modules\User\Models\User $user The user to confirm the password for.
     * @param string $password The password to confirm.
     *
     * @return bool True if the password matches, false otherwise.
     */
    public function confirmPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }
}
