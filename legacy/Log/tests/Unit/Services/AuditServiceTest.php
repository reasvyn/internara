<?php

declare(strict_types=1);

namespace Modules\Log\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\Log\Models\AuditLog;
use Modules\Log\Services\AuditService;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('log.retention_days', 365);
});

test('it logs an audit event with PII masking', function () {
    $service = new AuditService;

    $service->log('user_updated', 'Modules\User\Models\User', 'user-123', [
        'email' => 'test@example.com',
        'password' => 'secret123',
        'name' => 'John Doe',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'subject_type' => 'Modules\User\Models\User',
        'subject_id' => 'user-123',
        'action' => 'user_updated',
    ]);

    $log = AuditLog::first();
    expect($log->payload['name'])->toBe('John Doe');
    expect($log->payload['email'])->not->toBe('test@example.com'); // Should be masked
    expect($log->payload['password'])->not->toBe('secret123'); // Should be masked
});

test('it logs security events', function () {
    $service = new AuditService;

    $service->logSecurity('login_failed', [
        'email' => 'hacker@example.com',
        'ip_address' => '192.168.1.1',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'subject_type' => 'security',
        'action' => 'login_failed',
    ]);
});

test('it logs data changes with comparison', function () {
    $service = new AuditService;

    $oldValues = ['name' => 'Old Name', 'email' => 'old@example.com'];
    $newValues = ['name' => 'New Name', 'email' => 'new@example.com'];

    $service->logDataChange(
        'Modules\User\Models\User',
        'user-123',
        $oldValues,
        $newValues,
    );

    $log = AuditLog::where('action', 'updated')->first();
    expect($log)->not->toBeNull();
    expect($log->payload['changed_fields'])->toContain('name');
    expect($log->payload['changed_fields'])->toContain('email');
});

test('it does not log when no changes detected', function () {
    $service = new AuditService;

    $values = ['name' => 'Same Name'];

    $service->logDataChange(
        'Modules\User\Models\User',
        'user-123',
        $values,
        $values,
    );

    expect(AuditLog::count())->toBe(0);
});

test('it queries audit logs with filters', function () {
    // Create test data
    AuditLog::create([
        'user_id' => 'user-1',
        'subject_type' => 'Modules\Student\Models\Student',
        'subject_id' => 'student-1',
        'action' => 'created',
        'payload' => ['name' => 'John'],
        'ip_address' => '10.0.0.1',
        'created_at' => now(),
    ]);

    AuditLog::create([
        'user_id' => 'user-2',
        'subject_type' => 'Modules\Teacher\Models\Teacher',
        'subject_id' => 'teacher-1',
        'action' => 'updated',
        'payload' => ['name' => 'Jane'],
        'ip_address' => '10.0.0.2',
        'created_at' => now(),
    ]);

    $service = new AuditService;

    // Filter by subject type
    $results = $service->query(['subject_type' => 'Modules\Student\Models\Student']);
    expect($results->total())->toBe(1);

    // Filter by action
    $results = $service->query(['action' => 'updated']);
    expect($results->total())->toBe(1);

    // Filter by IP
    $results = $service->query(['ip_address' => '10.0.0.1']);
    expect($results->total())->toBe(1);
});

test('it gets module statistics', function () {
    // Create test data for User module
    for ($i = 0; $i < 5; $i++) {
        AuditLog::create([
            'subject_type' => 'Modules\User\Models\User',
            'action' => $i < 3 ? 'created' : 'updated',
            'created_at' => now()->subDays($i),
        ]);
    }

    $service = new AuditService;
    $stats = $service->getModuleStats('User');

    expect($stats['module'])->toBe('User');
    expect($stats['total_events'])->toBe(5);
    expect($stats['events_by_action']['created'])->toBe(3);
    expect($stats['events_by_action']['updated'])->toBe(2);
});

test('it exports logs for compliance', function () {
    AuditLog::create([
        'subject_type' => 'Modules\Student\Models\Student',
        'action' => 'created',
        'payload' => ['name' => 'John'],
        'ip_address' => '10.0.0.1',
        'created_at' => now(),
    ]);

    $service = new AuditService;
    $export = $service->exportForCompliance(['subject_type' => 'Modules\Student\Models\Student']);

    expect($export)->toContain('Module');
    expect($export)->toContain('Student');
    expect($export)->toContain('created');
});

test('it purges old logs based on retention policy', function () {
    // Create old log (400 days ago)
    AuditLog::create([
        'subject_type' => 'Modules\User\Models\User',
        'action' => 'old_action',
        'created_at' => now()->subDays(400),
    ]);

    // Create recent log
    AuditLog::create([
        'subject_type' => 'Modules\User\Models\User',
        'action' => 'new_action',
        'created_at' => now(),
    ]);

    $service = new AuditService;
    $deleted = $service->purgeOldLogs(365);

    expect($deleted)->toBe(1);
    expect(AuditLog::count())->toBe(1);
    expect(AuditLog::first()->action)->toBe('new_action');
});

test('it verifies audit trail integrity', function () {
    // Create valid logs
    for ($i = 0; $i < 10; $i++) {
        AuditLog::create([
            'user_id' => 'user-'.$i,
            'subject_type' => 'Modules\User\Models\User',
            'action' => 'test_action',
            'payload' => ['field' => 'value'],
            'created_at' => now(),
        ]);
    }

    $service = new AuditService;
    $result = $service->verifyIntegrity();

    expect($result['total_checked'])->toBe(10);
    expect($result['issues_found'])->toBe(0);
    expect($result['integrity_score'])->toBe(100.0);
});

test('it detects integrity issues', function () {
    // Create log with invalid payload
    $log = AuditLog::create([
        'subject_type' => 'Modules\User\Models\User',
        'action' => 'test_action',
        'payload' => 'invalid-json', // Invalid payload
        'created_at' => now(),
    ]);

    $service = new AuditService;
    $result = $service->verifyIntegrity();

    expect($result['issues_found'])->toBeGreaterThan(0);
});

test('it gets all auditable modules', function () {
    $service = new AuditService;
    $modules = $service->getAuditableModules();

    expect($modules)->toBeArray();
    expect($modules)->toHaveKey('User');
    expect($modules)->toHaveKey('Student');
    expect($modules['User']['has_audit_trait'])->toBeTrue();
});
