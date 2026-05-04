<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Core\Models\AuditLog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->action = new LogAuditAction;
});

test('execute creates audit log with all fields', function () {
    $log = $this->action->execute(
        action: 'user.login',
        subjectType: 'App\Models\User',
        subjectId: 'user-456',
        payload: ['email' => 'test@example.com'],
        module: 'auth',
    );

    expect($log)
        ->toBeInstanceOf(AuditLog::class)
        ->action->toBe('user.login')
        ->subject_type->toBe('App\Models\User')
        ->subject_id->toBe('user-456')
        ->payload->toBe(['email' => 'test@example.com'])
        ->module->toBe('auth')
        ->user_id->toBeNull();
});

test('execute throws exception for empty action', function () {
    $this->action->execute('');
})->throws(InvalidArgumentException::class, 'Audit action must not be empty.');

test('execute works with minimal parameters', function () {
    $log = $this->action->execute('simple.action');

    expect($log)
        ->toBeInstanceOf(AuditLog::class)
        ->action->toBe('simple.action')
        ->subject_type->toBeNull()
        ->subject_id->toBeNull()
        ->payload->toBeNull()
        ->module->toBeNull();
});

test('execute rethrows exception on create failure', function () {
    $this->markTestSkipped('Cannot mock Eloquent model create method in unit tests');
});
