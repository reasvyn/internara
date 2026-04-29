<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Setup\Models\Setup;
use Modules\Setup\Services\Contracts\SetupService as Contract;
use Modules\Shared\Services\BaseService;

/**
 * Setup Service Implementation
 *
 * [S1 - Secure] Encrypted tokens, audit logging, atomic operations
 * [S2 - Sustain] Clear error messages, proper logging
 * [S3 - Scalable] UUID-based, independent entity
 */
class SetupService extends BaseService implements Contract
{
    /**
     * Get or create the setup record
     */
    public function getSetup(): Setup
    {
        $setup = Setup::first();

        if ($setup === null) {
            $setup = Setup::create([
                'is_installed' => false,
                'completed_steps' => [],
            ]);

            activity('setup')
                ->withProperties(['setup_id' => $setup->id])
                ->log('Setup record created');
        }

        return $setup;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled(): bool
    {
        $setup = $this->getSetup();

        return $setup->is_installed === true;
    }

    /**
     * {@inheritdoc}
     */
    public function generateToken(): string
    {
        $setup = $this->getSetup();
        $plainToken = \Illuminate\Support\Str::random(64);

        $setup->setToken($plainToken);
        $setup->token_expires_at = now()->addHours(24);
        $setup->save();

        activity('setup')
            ->performedOn($setup)
            ->withProperties(['expires_at' => $setup->token_expires_at])
            ->log('setup_token_generated');

        return $plainToken;
    }

    /**
     * {@inheritdoc}
     */
    public function validateToken(string $token): bool
    {
        $setup = $this->getSetup();

        if ($setup->isTokenExpired()) {
            Log::warning('Setup token expired', ['setup_id' => $setup->id]);

            return false;
        }

        $isValid = $setup->tokenMatches($token);

        activity('setup')
            ->performedOn($setup)
            ->withProperties(['valid' => $isValid])
            ->log('setup_token_validated');

        return $isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function completeStep(string $step, array $data = []): void
    {
        DB::transaction(function () use ($setup, $step, $data) {
            $setup = $this->getSetup();

            $setup->completeStep($step);

            // Store related IDs if provided
            if (isset($data['admin_id'])) {
                $setup->admin_id = $data['admin_id'];
            }
            if (isset($data['school_id'])) {
                $setup->school_id = $data['school_id'];
            }
            if (isset($data['department_id'])) {
                $setup->department_id = $data['department_id'];
            }
            if (isset($data['internship_id'])) {
                $setup->internship_id = $data['internship_id'];
            }

            $setup->save();

            activity('setup')
                ->performedOn($setup)
                ->withProperties(['step' => $step, 'data' => $data])
                ->log('setup_step_completed');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(Setup $setup, string $adminId): void
    {
        DB::transaction(function () use ($setup, $adminId) {
            $setup->finalize($adminId);

            activity('setup')
                ->performedOn($setup)
                ->withProperties([
                    'admin_id' => $adminId,
                    'completed_steps' => $setup->completed_steps,
                ])
                ->log('setup_finalized');

            event(new \Modules\Setup\Events\SetupFinalized(
                schoolName: $setup->school?->name ?? null,
                installedAt: now()->toIso8601String(),
            ));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getProgress(Setup $setup): float
    {
        $totalSteps = 5; // welcome, school, account, department, internship, complete
        $completed = count(array_filter($setup->completed_steps ?? []));

        return min(100.0, ($completed / $totalSteps) * 100);
    }
}
