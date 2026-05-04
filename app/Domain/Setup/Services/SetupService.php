<?php

declare(strict_types=1);

namespace App\Domain\Setup\Services;

use App\Domain\Setup\Events\SetupFinalized;
use App\Domain\Setup\Models\Setup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Setup Service Implementation.
 * Centralized logic for system installation state and web wizard.
 *
 * S1 - Secure: Encrypted session tokens, lock file protection, atomic steps.
 * S2 - Sustain: Clean state management, persistence via session and DB.
 * S3 - Scalable: Stateless token validation for CLI-to-Web handover.
 */
class SetupService
{
    /**
     * Define the steps in the setup wizard.
     */
    public const array STEPS = [
        1 => 'welcome',
        2 => 'school',
        3 => 'department',
        4 => 'account',
        5 => 'internship',
        6 => 'finalize',
        7 => 'complete',
    ];

    /**
     * Check if the application is fully installed.
     */
    public function isInstalled(): bool
    {
        return File::exists(storage_path('app/.installed'));
    }

    /**
     * Generate a new encrypted setup token for session usage.
     */
    public function generateToken(): string
    {
        $token = Str::random(64);
        session(['setup_token' => $token]);

        return $token;
    }

    /**
     * Generate a stateless token for CLI-to-Web handover.
     */
    public function generateCliToken(): string
    {
        $token = Str::random(32);

        // Save to DB for stateless validation
        $setup = $this->getSetup();
        $setup->setToken($token);

        return $token;
    }

    /**
     * Clear CLI token from database.
     */
    public function clearCliToken(): void
    {
        $setup = $this->getSetup();
        $setup->setup_token = null;
        $setup->token_expires_at = null;
        $setup->save();
    }

    /**
     * Validate a token against the session or database.
     */
    public function validateToken(string $token): bool
    {
        // 1. Check Session
        if (session('setup_token') === $token) {
            return true;
        }

        // 2. Check Database (CLI handover)
        $setup = $this->getSetup();
        if ($setup->tokenMatches($token) && ! $setup->isTokenExpired()) {
            return true;
        }

        return false;
    }

    /**
     * Mark a session as authorized for setup via a specific token.
     */
    public function authorizeSession(string $token): void
    {
        if ($this->validateToken($token)) {
            session(['setup_authorized' => true, 'setup_token' => $token]);
        }
    }

    /**
     * Check if the current session is authorized for setup.
     */
    public function isSessionAuthorized(): bool
    {
        return (bool) session('setup_authorized', false);
    }

    /**
     * Check if the 5-minute finalization window is active.
     */
    public function isFinalizationWindowActive(): bool
    {
        $finalizedAt = session('setup_finalized_at');

        if (! $finalizedAt) {
            return false;
        }

        return now()->diffInMinutes($finalizedAt) <= 5;
    }

    /**
     * Set the current step in the session.
     */
    public function setCurrentStep(int $step): void
    {
        session(['setup_current_step' => $step]);
    }

    /**
     * Get the current step from session or default.
     */
    public function getCurrentStep(): int
    {
        return (int) session('setup_current_step', 1);
    }

    /**
     * Check if a setup step has been completed.
     */
    public function isStepCompleted(string $step): bool
    {
        return $this->getSetup()->isStepCompleted($step);
    }

    /**
     * Get or create the persistent setup record.
     */
    public function getSetup(): Setup
    {
        return Setup::firstOrCreate([], [
            'is_installed' => false,
            'completed_steps' => [],
        ]);
    }

    /**
     * Mark a step as completed and optionally store associated entity IDs.
     */
    public function completeStep(string $step, array $ids = []): void
    {
        $setup = $this->getSetup();
        $setup->completeStep($step);

        foreach ($ids as $key => $value) {
            if (in_array($key, ['admin_id', 'school_id', 'department_id', 'internship_id'])) {
                $setup->{$key} = $value;
            }
        }

        $setup->save();

        // Persist to session for faster access during wizard
        $completed = session('setup_completed_steps', []);
        if (! in_array($step, $completed)) {
            $completed[] = $step;
            session(['setup_completed_steps' => $completed]);
        }
    }

    /**
     * Get progress percentage.
     */
    public function getProgress(): float
    {
        $setup = $this->getSetup();
        $completedCount = count($setup->completed_steps ?? []);

        return round(($completedCount / count(self::STEPS)) * 100, 2);
    }

    /**
     * Clear all setup-related session data.
     */
    public function clearSession(): void
    {
        session()->forget(['setup_token', 'setup_current_step', 'setup_completed_steps', 'setup_authorized']);
    }

    /**
     * Reset the setup state (for re-installation).
     */
    public function reset(): string
    {
        if (File::exists(storage_path('app/.installed'))) {
            File::delete(storage_path('app/.installed'));
        }

        $setup = $this->getSetup();
        $setup->is_installed = false;
        $setup->completed_steps = [];
        $setup->admin_id = null;
        $setup->school_id = null;
        $setup->department_id = null;
        $setup->internship_id = null;
        $setup->save();

        $this->clearSession();

        return $this->generateToken();
    }

    /**
     * Finalize the installation.
     */
    public function finalize(): void
    {
        $setup = $this->getSetup();
        $setup->finalize();

        File::put(
            storage_path('app/.installed'),
            json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => config('app.version', '0.1.0'),
            ], JSON_PRETTY_PRINT)
        );

        session(['setup_finalized_at' => now()]);

        event(new SetupFinalized(
            schoolName: $setup->school?->name,
            installedAt: now()->toIso8601String()
        ));

        $this->clearSession();
    }
}
