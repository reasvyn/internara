<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Restore app_info.json if missing (from previous test cleanup)
        $appInfoPath = base_path('app_info.json');
        $appInfoBackup = base_path('app_info.json.backup');
        if (! File::exists($appInfoPath) && File::exists($appInfoBackup)) {
            File::copy($appInfoBackup, $appInfoPath);
        }

        // Mark app as installed for tests (create lock file)
        $lockPath = storage_path('app/.installed');
        if (! File::exists($lockPath)) {
            File::ensureDirectoryExists(dirname($lockPath));
            File::put($lockPath, json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => 'testing',
            ], JSON_PRETTY_PRINT));
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }

    /**
     * Cleanup after tests - restore app_info.json if moved.
     */
    protected function tearDown(): void
    {
        $path = base_path('app_info.json');
        $backup = base_path('app_info.json.backup');

        if (! File::exists($path) && File::exists($backup)) {
            File::move($backup, $path);
        }

        parent::tearDown();
    }
}
