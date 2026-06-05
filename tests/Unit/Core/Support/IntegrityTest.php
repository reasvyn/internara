<?php

declare(strict_types=1);

use App\Core\Support\Integrity;
use Illuminate\Support\Facades\Log;

test('Integrity verify executes without error when composer.json is valid', function () {
    Integrity::verify();
    expect(true)->toBeTrue();
});

test('Integrity verify logs warning on missing composer.json in testing environment', function () {
    $originalPath = dirname(__DIR__, 5).'/composer.json';
    $backupPath = $originalPath.'.bak';

    if (! file_exists($originalPath)) {
        test()->markTestSkipped('composer.json not found, cannot simulate missing file');
    }

    rename($originalPath, $backupPath);

    Log::shouldReceive('warning')
        ->once()
        ->with(Mockery::on(fn ($msg) => str_contains($msg, 'composer.json')), Mockery::any());

    Integrity::verify();

    rename($backupPath, $originalPath);
});
