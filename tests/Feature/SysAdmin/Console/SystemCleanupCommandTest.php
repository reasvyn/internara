<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Console;

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->logDir = storage_path('logs');
    if (! File::isDirectory($this->logDir)) {
        File::makeDirectory($this->logDir, 0755, true);
    }
});

afterEach(function () {
    @unlink($this->logDir.'/laravel-2000-01-01.log');
    @unlink($this->logDir.'/laravel-'.date('Y-m-d').'.log');
    @unlink($this->logDir.'/some-other-file.txt');
});

test('system:cleanup runs all tasks with --force and prunes old logs', function () {
    $oldLog = $this->logDir.'/laravel-2000-01-01.log';
    $newLog = $this->logDir.'/laravel-'.date('Y-m-d').'.log';
    $otherFile = $this->logDir.'/some-other-file.txt';

    File::put($oldLog, 'old');
    File::put($newLog, 'new');
    File::put($otherFile, 'other');

    touch($oldLog, time() - (40 * 24 * 60 * 60));
    touch($newLog, time());
    touch($otherFile, time() - (40 * 24 * 60 * 60));

    $this->artisan('system:cleanup --force --log-retention=30')
        ->expectsOutputToContain(__('setup.system.cleanup_starting'))
        ->expectsOutputToContain(__('setup.system.cleanup_completed'))
        ->assertSuccessful();

    expect(File::exists($oldLog))->toBeFalse();
    expect(File::exists($newLog))->toBeTrue();
    expect(File::exists($otherFile))->toBeTrue();
});

test('system:cleanup aborts when confirmation is declined', function () {
    $this->artisan('system:cleanup')
        ->expectsConfirmation(__('setup.system.cleanup_confirm'), 'no')
        ->assertExitCode(0);
});

test('system:cleanup proceeds when confirmation is accepted', function () {
    $this->artisan('system:cleanup')
        ->expectsConfirmation(__('setup.system.cleanup_confirm'), 'yes')
        ->expectsOutputToContain(__('setup.system.cleanup_starting'))
        ->assertSuccessful();
});
