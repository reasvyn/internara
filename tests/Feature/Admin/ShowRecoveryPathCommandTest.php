<?php

declare(strict_types=1);

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

describe('ShowRecoveryPathCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('admin:recovery-path');
    });

    it('shows path and exists status when file exists', function () {
        File::put(storage_path('app/private/.recovery-key'), 'content');

        $this->artisan('admin:recovery-path')
            ->assertExitCode(0);
    });

    it('shows path and missing status when file is missing', function () {
        $path = storage_path('app/private/.recovery-key');
        if (File::exists($path)) {
            File::delete($path);
        }

        $this->artisan('admin:recovery-path')
            ->assertExitCode(0);
    });
});
