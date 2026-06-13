<?php

declare(strict_types=1);

use App\SysAdmin\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('factory creates a valid log entry', function () {
    $log = GdprDeletionLog::factory()->create();

    expect($log)->toBeInstanceOf(GdprDeletionLog::class);
    expect($log->user_id)->not->toBeNull();
});

test('metadata snapshot is cast to array', function () {
    $log = GdprDeletionLog::factory()->create();

    expect($log->metadata_snapshot)->toBeArray();
    expect($log->metadata_snapshot)->toHaveKeys(['name', 'email', 'username']);
});

test('updated at column is disabled', function () {
    expect(GdprDeletionLog::UPDATED_AT)->toBeNull();
});

test('log entry can be created with custom data', function () {
    $snapshot = ['name' => 'Jane Doe', 'email' => 'jane@example.com'];
    $log = GdprDeletionLog::factory()->create([
        'user_id' => 'custom-uuid',
        'metadata_snapshot' => $snapshot,
    ]);

    expect($log->user_id)->toBe('custom-uuid');
    expect($log->metadata_snapshot)->toBe($snapshot);
});
