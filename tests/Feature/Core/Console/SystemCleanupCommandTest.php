<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

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

test('system:cleanup command runs all tasks successfully with --force option', function () {
    $oldLog = $this->logDir.'/laravel-2000-01-01.log';
    $newLog = $this->logDir.'/laravel-'.date('Y-m-d').'.log';
    $otherFile = $this->logDir.'/some-other-file.txt';

    File::put($oldLog, 'old content');
    File::put($newLog, 'new content');
    File::put($otherFile, 'other content');

    touch($oldLog, time() - (40 * 24 * 60 * 60)); // 40 days old
    touch($newLog, time()); // recent
    touch($otherFile, time() - (40 * 24 * 60 * 60)); // 40 days old

    $this->artisan('system:cleanup --force --log-retention=30')
        ->expectsOutputToContain(__('setup.system.cleanup_starting'))
        ->expectsOutputToContain(__('setup.system.cleanup_completed'))
        ->assertSuccessful();

    expect(File::exists($oldLog))->toBeFalse();
    expect(File::exists($newLog))->toBeTrue();
    expect(File::exists($otherFile))->toBeTrue();
});

test('system:cleanup command aborts when confirmation prompt is declined', function () {
    $this->artisan('system:cleanup')
        ->expectsConfirmation(__('setup.system.cleanup_confirm'), 'no')
        ->assertExitCode(0);
});

test('system:cleanup command proceeds when confirmation prompt is accepted', function () {
    $this->artisan('system:cleanup')
        ->expectsConfirmation(__('setup.system.cleanup_confirm'), 'yes')
        ->expectsOutputToContain(__('setup.system.cleanup_starting'))
        ->assertSuccessful();
});
