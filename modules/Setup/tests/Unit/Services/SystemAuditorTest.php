<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Modules\Setup\Services\SystemAuditor;

beforeEach(function () {
    $this->service = new SystemAuditor;
});

test('it performs a full system audit', function () {
    $results = $this->service->audit();

    expect($results)
        ->toBeArray()
        ->toHaveKeys(['requirements', 'permissions', 'database']);
});

test('it checks PHP requirements', function () {
    $results = $this->service->checkRequirements();

    expect($results)
        ->toBeArray()
        ->toHaveKey('php_version')
        ->and($results['php_version'])
        ->toBeTrue();

    expect($results)->toHaveKeys(['extension_pdo', 'extension_mbstring', 'extension_xml']);
});

test('it simulates missing PHP extensions', function () {
    // This requires the service to use a method that can be mocked or a property that can be set
    // Assuming the service has a protected $requirements property or similar
    // Since I'm writing tests, I'll assume we can inject or mock the extension checker
    // In a real scenario, we might need to refactor the service to be more testable
    
    // For now, let's test the 'passes' logic with simulated failure data
    $auditor = Mockery::mock(SystemAuditor::class)->makePartial();
    $auditor->shouldReceive('checkRequirements')->andReturn([
        'php_version' => true,
        'extension_pdo' => false, // Failure
    ]);
    $auditor->shouldReceive('checkPermissions')->andReturn(['storage' => true]);
    $auditor->shouldReceive('checkDatabase')->andReturn(['connection' => true]);

    expect($auditor->passes())->toBeFalse();
});

test('it checks directory permissions', function () {
    $results = $this->service->checkPermissions();

    expect($results)
        ->toBeArray()
        ->toHaveKeys(['storage_directory', 'storage_logs', 'bootstrap_cache', 'env_file']);
});

test('it simulates read-only directory failure', function () {
    $auditor = Mockery::mock(SystemAuditor::class)->makePartial();
    $auditor->shouldReceive('checkRequirements')->andReturn(['php' => true]);
    $auditor->shouldReceive('checkPermissions')->andReturn([
        'storage_directory' => false, // Failure
    ]);
    $auditor->shouldReceive('checkDatabase')->andReturn(['connection' => true]);

    expect($auditor->passes())->toBeFalse();
});

test('it checks database connectivity', function () {
    $results = $this->service->checkDatabase();

    expect($results)->toBeArray()->toHaveKey('connection')->and($results['connection'])->toBeTrue();
});

test('it handles database connection failure', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Connection failed'));

    $results = $this->service->checkDatabase();

    expect($results)
        ->toBeArray()
        ->and($results['connection'])
        ->toBeFalse()
        ->and($results['message'])
        ->toBe('Connection failed');
});

test('it determines if all audits pass', function () {
    $result = $this->service->passes();

    expect($result)->toBeTrue();
});
