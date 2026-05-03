<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Setup\EnvAuditor;
use App\Services\Setup\SetupRequirementRegistry;

/**
 * S2 - Sustain: Unit tests for EnvAuditor pre-flight checks.
 * Verifies that the auditor correctly aggregates and reports system requirements.
 */
beforeEach(function () {
    app()->setLocale('en');
    $this->auditor = new EnvAuditor(new SetupRequirementRegistry);
});

test('it can perform a full system audit', function () {
    $result = $this->auditor->audit();

    expect($result)
        ->toBeArray()
        ->toHaveKeys(['passed', 'categories']);

    expect($result['categories'])
        ->toHaveKeys(['requirements', 'permissions', 'database', 'recommendations']);

    foreach ($result['categories'] as $category) {
        expect($category)
            ->toHaveKeys(['label', 'checks']);

        expect($category['checks'])->toBeArray();

        foreach ($category['checks'] as $check) {
            expect($check)->toHaveKeys(['name', 'status', 'message']);
        }
    }
});

test('it reports failure when a critical check fails', function () {
    $registry = new SetupRequirementRegistry;
    // Add a non-existent extension to force failure
    $registry->requireExtension('non_existent_extension_abc_123');

    $auditor = new EnvAuditor($registry);
    $result = $auditor->audit();

    expect($result['passed'])->toBeFalse();
});

test('it correctly sanitizes database error messages', function () {
    // Accessing private method for testing sanitization logic
    $reflection = new \ReflectionClass(EnvAuditor::class);
    $method = $reflection->getMethod('sanitizeError');
    $method->setAccessible(true);

    $sensitiveMessage = 'Access denied for user root@localhost (using password: YES)';
    $sanitized = $method->invoke($this->auditor, $sensitiveMessage);

    expect($sanitized)->not->toContain('root');
    expect($sanitized)->toContain('[redacted]');
});

test('it detects php version requirement', function () {
    $reflection = new \ReflectionClass(EnvAuditor::class);
    $method = $reflection->getMethod('checkPhpVersion');
    $method->setAccessible(true);

    $result = $method->invoke($this->auditor);

    expect($result['status'])->toBeIn(['pass', 'fail']);
    expect($result['name'])->toContain('PHP Version');
});
