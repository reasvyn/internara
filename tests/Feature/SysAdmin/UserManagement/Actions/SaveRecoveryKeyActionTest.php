<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\UserManagement\Actions;

use App\SysAdmin\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Support\Facades\File;
use RuntimeException;

test('save recovery key action creates directory and writes key file', function () {
    $dir = storage_path('app/private');
    $path = "{$dir}/.recovery-key";

    if (File::exists($path)) {
        File::delete($path);
    }

    $action = app(SaveRecoveryKeyAction::class);
    $result = $action->execute('test-recovery-key-12345');

    expect($result)->toBe($path);
    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)->toContain('test-recovery-key-12345');
    expect($content)->toContain('# INTERNARA RECOVERY KEY');

    File::delete($path);
});

test('save recovery key action throws exception on write failure', function () {
    $dir = storage_path('app/private');
    File::shouldReceive('exists')->with($dir)->once()->andReturnTrue();

    File::shouldReceive('put')->once()->andReturnFalse();

    $action = app(SaveRecoveryKeyAction::class);

    expect(fn () => $action->execute('test-key'))->toThrow(
        RuntimeException::class,
        'Failed to write recovery key',
    );
});
