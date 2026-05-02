<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Support\AppInfo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

/**
 * Manages setup wizard state using session and lock file.
 *
 * S1 - Secure: Token-based access, timing-safe comparison, lock file gate.
 * S2 - Sustain: No DB dependency during pre-installation phase.
 */
class SetupService
{
    /**
     * Path to the installation lock file.
     */
    private const LOCK_FILE = 'app/.installed';

    /**
     * Path to the CLI-generated setup token.
     */
    private const CLI_TOKEN_FILE = 'app/.setup_token';

    /**
     * Session key prefix for setup state.
     */
    private const SESSION_PREFIX = 'setup.';

    /**
     * Token expiry in hours.
     */
    private const TOKEN_TTL_HOURS = 24;

    /**
     * Setup steps in order.
     * Follows professional naming: snake_case, semantic clarity.
     */
    public const STEPS = ['welcome', 'school', 'account', 'department', 'internship', 'finalize', 'complete'];

    /**
     * Check if the application is already installed.
     */
    public function isInstalled(): bool
    {
        return File::exists(storage_path(self::LOCK_FILE));
    }

    /**
     * Generate a new setup token and store it in session.
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));

        Session::put(self::SESSION_PREFIX . 'token', Crypt::encryptString($token));
        Session::put(
            self::SESSION_PREFIX . 'token_expires_at',
            now()->addHours(self::TOKEN_TTL_HOURS)->toIso8601String(),
        );

        return $token;
    }

    /**
     * Generate a CLI-accessible setup token and store it in a file.
     * S1: Used for setup:install command to hand-off to Web Wizard.
     */
    public function generateCliToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours(self::TOKEN_TTL_HOURS)->toIso8601String();

        File::put(storage_path(self::CLI_TOKEN_FILE), $token . '|' . $expiresAt);

        return $token;
    }

    /**
     * Get the token from CLI storage if it exists and is valid.
     */
    public function getCliToken(): ?string
    {
        $path = storage_path(self::CLI_TOKEN_FILE);

        if (!File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $parts = explode('|', $content, 2);

        if (count($parts) < 2) {
            return null;
        }

        [$token, $expiresAt] = $parts;

        if (now()->greaterThan(Carbon::parse($expiresAt))) {
            File::delete($path);

            return null;
        }

        return $token;
    }

    /**
     * Get the current setup token from session.
     */
    public function getToken(): ?string
    {
        $encrypted = Session::get(self::SESSION_PREFIX . 'token');

        if ($encrypted === null) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate a token using timing-safe comparison.
     */
    public function validateToken(string $token): bool
    {
        // 1. Try session token
        $sessionToken = $this->getToken();
        if ($sessionToken !== null && !$this->isTokenExpired()) {
            if (hash_equals($sessionToken, $token)) {
                return true;
            }
        }

        // 2. Try CLI token
        $cliToken = $this->getCliToken();
        if ($cliToken !== null) {
            if (hash_equals($cliToken, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the setup token has expired.
     */
    public function isTokenExpired(): bool
    {
        $expiresAt = Session::get(self::SESSION_PREFIX . 'token_expires_at');

        if ($expiresAt === null) {
            return true;
        }

        return now()->greaterThan(Carbon::parse($expiresAt));
    }

    /**
     * Get the current step number (1-6).
     */
    public function getCurrentStep(): int
    {
        return (int) Session::get(self::SESSION_PREFIX . 'current_step', 1);
    }

    /**
     * Set the current step number.
     */
    public function setCurrentStep(int $step): void
    {
        Session::put(self::SESSION_PREFIX . 'current_step', max(1, min(7, $step)));
    }

    /**
     * Mark a step as completed.
     */
    public function completeStep(string $step): void
    {
        $steps = $this->getCompletedSteps();

        if (!in_array($step, $steps)) {
            $steps[] = $step;
            Session::put(self::SESSION_PREFIX . 'completed_steps', $steps);
        }
    }

    /**
     * Check if a step has been completed.
     */
    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->getCompletedSteps());
    }

    /**
     * Get all completed steps.
     *
     * @return array<int, string>
     */
    public function getCompletedSteps(): array
    {
        return Session::get(self::SESSION_PREFIX . 'completed_steps', []);
    }

    /**
     * Calculate setup progress percentage.
     */
    public function getProgress(): int
    {
        $completed = count($this->getCompletedSteps());

        // 'complete' step doesn't count toward progress
        $total = count(self::STEPS) - 1;

        if ($total === 0) {
            return 0;
        }

        return (int) round(($completed / $total) * 100);
    }

    /**
     * Store a setup entity ID for finalization audit.
     */
    public function storeEntityId(string $key, string $id): void
    {
        Session::put(self::SESSION_PREFIX . 'entity.' . $key, $id);
    }

    /**
     * Get a stored setup entity ID.
     */
    public function getEntityId(string $key): ?string
    {
        return Session::get(self::SESSION_PREFIX . 'entity.' . $key);
    }

    /**
     * Finalize the installation: create lock file, clear session state.
     */
    public function finalize(bool $clearSession = true): void
    {
        $lockContent = (string) json_encode(
            [
                'installed_at' => now()->toIso8601String(),
                'version' => AppInfo::version(),
            ],
            JSON_PRETTY_PRINT,
        );

        File::put(storage_path(self::LOCK_FILE), $lockContent);

        // Remove CLI token file if it exists
        if (File::exists(storage_path(self::CLI_TOKEN_FILE))) {
            File::delete(storage_path(self::CLI_TOKEN_FILE));
        }

        if ($clearSession) {
            $this->clearSession();
        } else {
            // S1: Set a short-lived finalization timestamp for Step 7 access
            Session::put(self::SESSION_PREFIX . 'finalized_at', now()->toIso8601String());
        }
    }

    /**
     * Clear all setup-related session data.
     */
    public function clearSession(): void
    {
        Session::forget(self::SESSION_PREFIX . 'token');
        Session::forget(self::SESSION_PREFIX . 'token_expires_at');
        Session::forget(self::SESSION_PREFIX . 'current_step');
        Session::forget(self::SESSION_PREFIX . 'completed_steps');
        Session::forget(self::SESSION_PREFIX . 'entity');
        Session::forget(self::SESSION_PREFIX . 'authorized');
        Session::forget('setup.token_input');
    }

    /**
     * Reset the setup state (remove lock file, clear session, generate new token).
     */
    public function reset(): string
    {
        if (File::exists(storage_path(self::LOCK_FILE))) {
            File::delete(storage_path(self::LOCK_FILE));
        }

        if (File::exists(storage_path(self::CLI_TOKEN_FILE))) {
            File::delete(storage_path(self::CLI_TOKEN_FILE));
        }

        $this->clearSession();

        return $this->generateToken();
    }

    /**
     * Mark the current session as authorized for a specific token.
     */
    public function authorizeSession(string $token): void
    {
        Session::put(self::SESSION_PREFIX . 'authorized', true);
        Session::put(self::SESSION_PREFIX . 'authorized_token', $token);
    }

    /**
     * Check if the current session is authorized and matches the current valid tokens.
     */
    public function isSessionAuthorized(): bool
    {
        $authorized = (bool) Session::get(self::SESSION_PREFIX . 'authorized', false);
        if (!$authorized) {
            return false;
        }

        $token = Session::get(self::SESSION_PREFIX . 'authorized_token');
        if ($token === null) {
            return false;
        }

        return $this->validateToken((string) $token);
    }

    /**
     * Check if the short window for viewing the setup summary is still active.
     * S1: Limits access to 5 minutes after finalization.
     */
    public function isFinalizationWindowActive(): bool
    {
        $finalizedAt = Session::get(self::SESSION_PREFIX . 'finalized_at');

        if ($finalizedAt === null) {
            return false;
        }

        return now()->diffInMinutes(Carbon::parse($finalizedAt)) < 5;
    }

    /**
     * Get the lock file content (if installed).
     */
     * @return array<string, mixed>|null
     */
    public function getLockData(): ?array
    {
        if (!$this->isInstalled()) {
            return null;
        }

        $content = File::get(storage_path(self::LOCK_FILE));

        return json_decode($content, true);
    }
}
