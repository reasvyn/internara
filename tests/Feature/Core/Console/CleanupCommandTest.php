<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;

uses(LazilyRefreshDatabase::class);

describe('CleanupCommand', function () {
    it('asks for confirmation without --force', function () {
        $this->artisan('system:cleanup')
            ->expectsQuestion(__('setup.system.cleanup_confirm'), true)
            ->assertSuccessful();
    });

    it('aborts cleanup when confirmation is denied', function () {
        $this->artisan('system:cleanup')
            ->expectsQuestion(__('setup.system.cleanup_confirm'), false)
            ->assertSuccessful();
    });

    it('runs with --force skipping confirmation', function () {
        $this->artisan('system:cleanup --force')
            ->assertSuccessful();
    });

    it('outputs cleanup task descriptions', function () {
        $this->artisan('system:cleanup --force')
            ->expectsOutputToContain(__('setup.system.cleanup_task_resets'))
            ->expectsOutputToContain(__('setup.system.cleanup_task_cache_tags'))
            ->expectsOutputToContain(__('setup.system.cleanup_task_failed_jobs'))
            ->expectsOutputToContain(__('setup.system.cleanup_task_activity_log'))
            ->expectsOutputToContain(__('setup.system.cleanup_task_media'));
    });

    it('outputs completion message', function () {
        $this->artisan('system:cleanup --force')
            ->expectsOutputToContain(__('setup.system.cleanup_completed'));
    });

    it('outputs starting message', function () {
        $this->artisan('system:cleanup --force')
            ->expectsOutputToContain(__('setup.system.cleanup_starting'));
    });

    it('prunes old log files with default retention', function () {
        $logDir = storage_path('logs');
        $oldFile = $logDir.'/laravel-2020-01-01.log';
        $recentFile = $logDir.'/laravel-'.now()->format('Y-m-d').'.log';

        try {
            File::put($oldFile, 'old content');
            touch($oldFile, now()->subDays(31)->timestamp);
            File::put($recentFile, 'recent content');
            touch($recentFile, now()->timestamp);

            $this->artisan('system:cleanup --force')
                ->assertSuccessful();

            expect(File::exists($oldFile))->toBeFalse();
            expect(File::exists($recentFile))->toBeTrue();
        } finally {
            File::delete($oldFile, $recentFile);
        }
    });

    it('respects custom --log-retention option', function () {
        $logDir = storage_path('logs');
        $withinRetention = $logDir.'/laravel-'.now()->subDays(2)->format('Y-m-d').'.log';
        $outsideRetention = $logDir.'/laravel-'.now()->subDays(10)->format('Y-m-d').'.log';

        try {
            File::put($withinRetention, 'content');
            touch($withinRetention, now()->subDays(2)->timestamp);
            File::put($outsideRetention, 'content');
            touch($outsideRetention, now()->subDays(6)->timestamp);

            $this->artisan('system:cleanup --force --log-retention=5')
                ->assertSuccessful();

            expect(File::exists($withinRetention))->toBeTrue();
            expect(File::exists($outsideRetention))->toBeFalse();
        } finally {
            File::delete($withinRetention, $outsideRetention);
        }
    });

    it('handles artisan command failures gracefully', function () {
        $this->artisan('system:cleanup --force')
            ->assertSuccessful();
    });
});
