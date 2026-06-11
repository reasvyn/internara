<?php

declare(strict_types=1);

namespace Tests\Feature\User\UserManagement\Actions;

use App\User\UserManagement\Actions\ReadRecoveryKeyAction;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $path = storage_path('app/private/.recovery-key');

    if (File::exists($path)) {
        File::delete($path);
    }
});

test('read recovery key action returns null when file does not exist', function () {
    $action = app(ReadRecoveryKeyAction::class);

    expect($action->execute())->toBeNull();
});

test('read recovery key action returns key content from file', function () {
    $action = app(ReadRecoveryKeyAction::class);
    $path = storage_path('app/private/.recovery-key');

    File::ensureDirectoryExists(dirname($path));
    File::put(
        $path,
        implode(PHP_EOL, [
            '# INTERNARA RECOVERY KEY',
            '# This key grants super admin access.',
            '# Generated: 2026-06-05T12:00:00+00:00',
            '',
            'plaintext-recovery-key-here',
            '',
        ]),
    );

    expect($action->execute())->toBe('plaintext-recovery-key-here');

    File::delete($path);
});

test('read recovery key action returns null when file contains only comments', function () {
    $action = app(ReadRecoveryKeyAction::class);
    $path = storage_path('app/private/.recovery-key');

    File::ensureDirectoryExists(dirname($path));
    File::put($path, implode(PHP_EOL, ['# INTERNARA RECOVERY KEY', '# Only comments here', '']));

    expect($action->execute())->toBeNull();

    File::delete($path);
});
