<?php

declare(strict_types=1);

use App\Domain\Setup\Models\Setup;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    app()->setLocale('en');
    $dir = storage_path('app/private');
    if (! File::isDirectory($dir)) {
        File::makeDirectory($dir, 0755, true);
    }
});

afterEach(function () {
    $path = storage_path('app/private/.recovery-key');
    if (File::exists($path)) {
        File::delete($path);
    }
});

describe('ShowRecoveryKeyCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('admin:recovery-show');
    });

    it('fails when recovery key file is missing', function () {
        $path = storage_path('app/private/.recovery-key');
        if (File::exists($path)) {
            File::delete($path);
        }

        $this->artisan('admin:recovery-show')
            ->assertExitCode(1);
    });

    it('fails when setup record has no recovery key', function () {
        File::put(storage_path('app/private/.recovery-key'), 'some-key-content');

        Setup::factory()->create([
            'recovery_key' => null,
        ]);

        $this->artisan('admin:recovery-show')
            ->assertExitCode(1);
    });
});
