<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Domain\Setup\Console\Commands\SetupInstallCommand;
use App\Domain\Setup\Models\Setup;

beforeEach(function () {
    Setup::truncate();
    Setup::create(['is_installed' => false, 'completed_steps' => []]);

    config(['setup.requirements.extensions' => []]);
    config(['setup.requirements.recommended_extensions' => []]);
    config(['setup.requirements.php_version' => PHP_VERSION]);
});

describe('SetupInstallCommand', function () {
    it('fails when already installed without --force', function () {
        Setup::truncate();
        Setup::create(['is_installed' => true, 'completed_steps' => []]);

        $this->artisan(SetupInstallCommand::class)
            ->assertExitCode(1);
    });

    it('succeeds with --check-only flag', function () {
        $this->artisan(SetupInstallCommand::class, ['--check-only' => true])
            ->assertExitCode(0);
    });

    it('fails when --force used in restricted environment', function () {
        config(['setup.force_allowed_environments' => ['production']]);

        $this->artisan(SetupInstallCommand::class, ['--force' => true])
            ->assertExitCode(1);
    });

    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('setup:install');
    });
});
