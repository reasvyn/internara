<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Models\AuditLog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    DB::statement('PRAGMA foreign_keys=OFF');
});

afterEach(function () {
    DB::statement('PRAGMA foreign_keys=ON');
});

test('audit log can be created with nullable user id', function () {
    $log = AuditLog::create([
        'action' => 'test.create',
    ]);

    expect($log)->toBeInstanceOf(AuditLog::class)
        ->action->toBe('test.create')
        ->user_id->toBeNull();
});

test('audit log cannot be updated', function () {
    $log = AuditLog::create([
        'action' => 'test.create',
    ]);

    Log::shouldReceive('critical')->once();

    $log->action = 'modified.action';
    $log->save();
})->throws(LogicException::class, 'Audit log entries are immutable and cannot be updated.');

test('audit log cannot be deleted', function () {
    $log = AuditLog::create([
        'action' => 'test.create',
    ]);

    Log::shouldReceive('critical')->once();

    $log->delete();
})->throws(LogicException::class, 'Audit log entries are immutable and cannot be deleted.');

test('scopeForUser filters by user id', function () {
    AuditLog::create(['action' => 'action1', 'user_id' => null]);
    AuditLog::create(['action' => 'action2', 'user_id' => null]);

    $results = AuditLog::forUser('nonexistent')->get();

    expect($results)->toHaveCount(0);
});

test('scopeForModule filters by module', function () {
    AuditLog::create(['action' => 'action1', 'module' => 'auth']);
    AuditLog::create(['action' => 'action2', 'module' => 'settings']);
    AuditLog::create(['action' => 'action3', 'module' => 'auth']);

    $results = AuditLog::forModule('auth')->get();

    expect($results)->toHaveCount(2);

    $results->each(fn ($log) => expect($log->module)->toBe('auth'));
});

test('scopeOfAction filters by action', function () {
    AuditLog::create(['action' => 'user.login']);
    AuditLog::create(['action' => 'user.logout']);
    AuditLog::create(['action' => 'user.login']);

    $results = AuditLog::ofAction('user.login')->get();

    expect($results)->toHaveCount(2);

    $results->each(fn ($log) => expect($log->action)->toBe('user.login'));
});

test('scopeForSubject filters by subject type', function () {
    AuditLog::create(['action' => 'action1', 'subject_type' => 'App\Models\User']);
    AuditLog::create(['action' => 'action2', 'subject_type' => 'App\Models\Setting']);
    AuditLog::create(['action' => 'action3', 'subject_type' => 'App\Models\User']);

    $results = AuditLog::forSubject('App\Models\User')->get();

    expect($results)->toHaveCount(2);

    $results->each(fn ($log) => expect($log->subject_type)->toBe('App\Models\User'));
});

test('scopeRecent returns limited records ordered by newest', function () {
    $first = AuditLog::create(['action' => 'first']);
    sleep(1);
    $second = AuditLog::create(['action' => 'second']);
    sleep(1);
    $third = AuditLog::create(['action' => 'third']);

    $results = AuditLog::recent(2)->get();

    expect($results)->toHaveCount(2)
        ->first()->action->toBe('third');
});

test('payload is cast to array', function () {
    $log = AuditLog::create([
        'action' => 'test.create',
        'payload' => ['key' => 'value', 'nested' => ['a' => 'b']],
    ]);

    expect($log->payload)->toBeArray()
        ->and($log->payload)->toHaveKey('key', 'value')
        ->and($log->payload)->toHaveKey('nested.a', 'b');
});

test('timestamps are enabled', function () {
    $log = AuditLog::create([
        'action' => 'test.create',
    ]);

    expect($log->created_at)->not->toBeNull()
        ->and($log->updated_at)->not->toBeNull();
});
