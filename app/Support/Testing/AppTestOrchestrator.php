<?php

declare(strict_types=1);

namespace App\Support\Testing;

use Illuminate\Support\Facades\Artisan;

/**
 * Orchestrates test environment setup and cleanup.
 *
 * S2 - Sustain: Centralized test management tools.
 */
class AppTestOrchestrator
{
    /**
     * Prepare the application for testing.
     */
    public function bootstrap(): void
    {
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed');
    }

    /**
     * Reset the testing environment.
     */
    public function teardown(): void
    {
        Artisan::call('migrate:rollback');
    }
}
