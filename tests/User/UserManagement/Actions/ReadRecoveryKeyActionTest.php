<?php

declare(strict_types=1);

use App\Modules\User\Domain\UserManagement\Actions\ReadRecoveryKeyAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    File::fake(); // Mock the File facade for file system operations
});

test('C9ZB6-FR-K1: returns the plaintext recovery key from a valid file', function () {
    // Arrange
    $plaintextKey = Str::random(64);
    $fileContent = "# Header Line\n\n{$plaintextKey}\n# Another comment";
    File::put(storage_path('app/private/.recovery-key'), $fileContent);
    $action = app(ReadRecoveryKeyAction::class);

    // Act
    $result = $action->execute();

    // Assert
    expect($result)->toBe($plaintextKey);
});

test('C9ZB6-FR-K1: returns null if the recovery key file does not exist', function () {
    // Arrange
    File::delete(storage_path('app/private/.recovery-key')); // Ensure file does not exist
    $action = app(ReadRecoveryKeyAction::class);

    // Act
    $result = $action->execute();

    // Assert
    expect($result)->toBeNull();
});

test('C9ZB6-FR-K1: returns null if the file exists but is empty', function () {
    // Arrange
    File::put(storage_path('app/private/.recovery-key'), '');
    $action = app(ReadRecoveryKeyAction::class);

    // Act
    $result = $action->execute();

    // Assert
    expect($result)->toBeNull();
});

test('C9ZB6-FR-K1: returns null if the file only contains comments and empty lines', function () {
    // Arrange
    $fileContent = "# Only comments\n\n# Another comment line";
    File::put(storage_path('app/private/.recovery-key'), $fileContent);
    $action = app(ReadRecoveryKeyAction::class);

    // Act
    $result = $action->execute();

    // Assert
    expect($result)->toBeNull();
});

test('C9ZB6-FR-K1: reads the first non-comment, non-empty line as the key', function () {
    // Arrange
    $firstKey = Str::random(32);
    $secondKey = Str::random(32);
    $fileContent = "# Comment 1\n\n{$firstKey}\n# Comment 2\n{$secondKey}";
    File::put(storage_path('app/private/.recovery-key'), $fileContent);
    $action = app(ReadRecoveryKeyAction::class);

    // Act
    $result = $action->execute();

    // Assert
    expect($result)->toBe($firstKey);
});
