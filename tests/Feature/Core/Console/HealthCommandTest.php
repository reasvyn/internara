<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('HealthCommand', function () {
    it('runs successfully when all checks pass', function () {
        $this->artisan('system:health')
            ->assertSuccessful();
    });

    it('lists environment service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.environment'));
    });

    it('lists setup status service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.setup_status'));
    });

    it('lists php version service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.php_version'));
    });

    it('lists extensions service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.extensions'));
    });

    it('lists recommended extensions service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.recommended_extensions'));
    });

    it('lists php memory service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.php_memory'));
    });

    it('lists database service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.database'));
    });

    it('lists migration status service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.migration_status'));
    });

    it('lists storage service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.storage'));
    });

    it('lists disk space service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.disk_space'));
    });

    it('lists queue service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.queue'));
    });

    it('lists cache service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.cache'));
    });

    it('lists app key service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.app_key'));
    });

    it('lists storage link service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.storage_link'));
    });

    it('lists maintenance mode service', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.maintenance_mode'));
    });

    it('supports json output mode', function () {
        $this->artisan('system:health --json')
            ->assertSuccessful();
    });

    it('outputs status column header in table mode', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain(__('setup.system.status'));
    });

    it('outputs OK for passing checks', function () {
        $this->artisan('system:health')
            ->expectsOutputToContain('OK');
    });
});
