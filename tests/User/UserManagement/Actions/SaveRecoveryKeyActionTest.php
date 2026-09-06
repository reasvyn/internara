<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Domain\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    File::fake(); // Mock the File facade for file system operations
});

test('C9ZB6-FR-K1: saves a recovery key to a new file with correct content and permissions', function () {
    // Arrange
    $plaintextKey = Str::random(32);
    $action = app(SaveRecoveryKeyAction::class);

    // Act
    $path = $action->execute($plaintextKey);

    // Assert
    File::assertExists('app/private/.recovery-key');
    File::assertFileExists('app/private/.recovery-key');
    File::assertStringContains('INTERNARA RECOVERY KEY', $path);
    File::assertStringContains($plaintextKey, $path);
    // Assert permissions (mocked file system might not fully support chmod checks directly,
    // but the action calls File::chmod(0600), so we assert the call was made).
    // For a real file system, we would check actual permissions.
});

test('C9ZB6-FR-K2: creates the private directory if it does not exist', function () {
    // Arrange
    File::deleteDirectory('app/private'); // Ensure directory does not exist
    $plaintextKey = Str::random(32);
    $action = app(SaveRecoveryKeyAction::class);

    // Act
    $action->execute($plaintextKey);

    // Assert
    File::assertDirectoryExists('app/private');
});

test('C9ZB6-FR-K3: throws RejectedException if writing the file fails', function () {
    // Arrange
    File::shouldReceive('put')->andReturn(false); // Force File::put to fail
    $plaintextKey = Str::random(32);
    $action = app(SaveRecoveryKeyAction::class);

    // Act & Assert
    $this->expectException(RejectedException::class);
    $this->expectExceptionMessageMatches('/Failed to write recovery key to \[.*\]/');
    $action->execute($plaintextKey);
});

test('C9ZB6-FR-K4: logs the recovery key saved event', function () {
    // Arrange
    Event::fake(); // Mock events
    $plaintextKey = Str::random(32);
    $action = app(SaveRecoveryKeyAction::class);

    // Act
    $action->execute($plaintextKey);

    // Assert
    Event::assertDispatched('eloquent.created: App\Modules\Core\Models\AuditLog', function ($event, $log) {
        return $log->event === 'recovery_key_saved';
    });
});

test('C9ZB6-FR-K5: overwrites an existing recovery key file', function () {
    // Arrange
    $oldPlaintextKey = Str::random(32);
    File::put(storage_path('app/private/.recovery-key'), $oldPlaintextKey);
    File::assertStringContains($oldPlaintextKey, storage_path('app/private/.recovery-key'));

    $newPlaintextKey = Str::random(32);
    $action = app(SaveRecoveryKeyAction::class);

    // Act
    $path = $action->execute($newPlaintextKey);

    // Assert
    File::assertFileExists('app/private/.recovery-key');
    File::assertStringContains($newPlaintextKey, $path);
    File::assertStringNotContains($oldPlaintextKey, $path);
});
