<?php

declare(strict_types=1);

namespace Modules\Setup\Services\Contracts;

use Modules\Setup\Models\Setup;

/**
 * Contract for Setup Service
 *
 * [S1 - Secure] Manages setup state with encryption and audit
 * [S2 - Sustain] Clear business intent
 * [S3 - Scalable] UUID-based, stateless tokens
 */
interface SetupService
{
    /**
     * Get or create the setup record
     */
    public function getSetup(): Setup;

    /**
     * Check if system is installed
     */
    public function isInstalled(): bool;

    /**
     * Generate and store encrypted setup token
     */
    public function generateToken(): string;

    /**
     * Validate provided token (timing-safe, expiry check)
     */
    public function validateToken(string $token): bool;

    /**
     * Complete a setup step (atomic)
     */
    public function completeStep(string $step, array $data = []): void;

    /**
     * Finalize setup (clear tokens, mark complete)
     */
    public function finalize(Setup $setup, string $adminId): void;

    /**
     * Get setup progress percentage
     */
    public function getProgress(Setup $setup): float;
}
