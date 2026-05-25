<?php

declare(strict_types=1);

use App\Domain\Setup\Models\Setup;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    app()->setLocale('en');
});

describe('ShowRecoveryKeyCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('admin:recovery-show');
    });

    it('fails when recovery key file is missing', function () {
        File::shouldReceive('exists')
            ->with(storage_path('app/private/.recovery-key'))
            ->andReturn(false);

        $this->artisan('admin:recovery-show')
            ->assertExitCode(1);
    });

    it('fails when setup record has no recovery key', function () {
        File::shouldReceive('exists')
            ->with(storage_path('app/private/.recovery-key'))
            ->andReturn(true);

        Setup::factory()->create([
            'recovery_key' => null,
        ]);

        $this->artisan('admin:recovery-show')
            ->assertExitCode(1);
    });

    it('aborts when confirmation is denied (non-interactive)', function () {
        File::shouldReceive('exists')
            ->with(storage_path('app/private/.recovery-key'))
            ->andReturn(true);

        Setup::factory()->create([
            'recovery_key' => 'hashed-key',
        ]);

        $this->artisan('admin:recovery-show')
            ->assertExitCode(0);
    });
});
