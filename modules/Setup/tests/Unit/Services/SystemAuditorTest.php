<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
        ->and($results['php_version'])->toBeTrue(); // Assuming test runner is on 8.4+ as per project specs

    // Check for some common extensions defined in the service
    expect($results)->toHaveKeys(['extension_pdo', 'extension_mbstring', 'extension_xml']);
});

test('it checks directory permissions', function () {
    // Mocking storage paths or using real ones in test environment
    $results = $this->service->checkPermissions();

    expect($results)
        ->toBeArray()
        ->toHaveKeys(['storage_directory', 'storage_logs', 'bootstrap_cache', 'env_file']);
});

test('it checks database connectivity', function () {
    // In test environment, DB should usually be connected (sqlite in memory)
    $results = $this->service->checkDatabase();

    expect($results)
        ->toBeArray()
        ->toHaveKey('connection')
        ->and($results['connection'])->toBeTrue();
});

test('it handles database connection failure', function () {
    DB::shouldReceive('connection')->andThrow(new \Exception('Connection failed'));

    $results = $this->service->checkDatabase();

    expect($results)
        ->toBeArray()
        ->and($results['connection'])->toBeFalse()
        ->and($results['message'])->toBe('Connection failed');
});

test('it determines if all audits pass', function () {
    // In a clean test environment, everything should pass
    $result = $this->service->passes();

    expect($result)->toBeTrue();
});
