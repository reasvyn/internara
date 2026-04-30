<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Support\AppInfo;
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
    public const STEPS = [
        'welcome',
        'school',
        'account',
        'department',
        'internship',
        'finalize',
    ];

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
        Session::put(self::SESSION_PREFIX . 'token_expires_at', now()->addHours(self::TOKEN_TTL_HOURS)->toIso8601String());

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

        return Crypt::decryptString($encrypted);
    }

    /**
     * Validate a token using timing-safe comparison.
     */
    public function validateToken(string $token): bool
    {
        $stored = $this->getToken();

        if ($stored === null) {
            return false;
        }

        if ($this->isTokenExpired()) {
            return false;
        }

        return hash_equals($stored, $token);
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

        return now()->greaterThan(\Illuminate\Support\Carbon::parse($expiresAt));
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

        if (! in_array($step, $steps)) {
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
    public function finalize(): void
    {
        $lockContent = json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => AppInfo::version(),
        ], JSON_PRETTY_PRINT);

        File::put(storage_path(self::LOCK_FILE), $lockContent);

        $this->clearSession();
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
    }

    /**
     * Reset the setup state (remove lock file, clear session, generate new token).
     */
    public function reset(): string
    {
        if (File::exists(storage_path(self::LOCK_FILE))) {
            File::delete(storage_path(self::LOCK_FILE));
        }

        $this->clearSession();

        return $this->generateToken();
    }

    /**
     * Check if the current session is authorized for setup access.
     */
    public function isSessionAuthorized(): bool
    {
        return (bool) Session::get(self::SESSION_PREFIX . 'authorized', false);
    }

    /**
     * Mark the current session as authorized.
     */
    public function authorizeSession(): void
    {
        Session::put(self::SESSION_PREFIX . 'authorized', true);
    }

    /**
     * Get the lock file content (if installed).
     *
     * @return array<string, mixed>|null
     */
    public function getLockData(): ?array
    {
        if (! $this->isInstalled()) {
            return null;
        }

        $content = File::get(storage_path(self::LOCK_FILE));

        return json_decode($content, true);
    }
}
