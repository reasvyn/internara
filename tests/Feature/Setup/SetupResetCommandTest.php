<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Setup\Models\Setup;

beforeEach(function () {
    app()->setLocale('en');
    Setup::truncate();
});

describe('SetupResetCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('setup:reset');
    });

    it('fails when system is already installed', function () {
        Setup::create(['is_installed' => true, 'completed_steps' => []]);

        $this->artisan('setup:reset')
            ->assertExitCode(1);
    });

    it('generates new setup token when not installed', function () {
        Setup::create(['is_installed' => false, 'completed_steps' => []]);

        $this->artisan('setup:reset')
            ->assertExitCode(0)
            ->expectsOutputToContain('URL:')
            ->expectsOutputToContain('Token:')
            ->expectsOutputToContain('Expires:');
    });

    it('generates token when no setup record exists', function () {
        $this->artisan('setup:reset')
            ->assertExitCode(0);
    });
});
