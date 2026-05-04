<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Setup\Services\EnvAuditor;

/**
 * S2 - Sustain: Unit tests for EnvAuditor pre-flight checks.
 * Verifies that the auditor correctly aggregates and reports system requirements.
 */
beforeEach(function () {
    app()->setLocale('en');
    $this->auditor = new EnvAuditor;
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

test('it correctly sanitizes database error messages', function () {
    // Accessing private method for testing sanitization logic
    $reflection = new \ReflectionClass(EnvAuditor::class);
    $method = $reflection->getMethod('sanitizeError');
    $method->setAccessible(true);

    $sensitiveMessage = "Access denied for user 'root'@'localhost'";
    $sanitized = $method->invoke($this->auditor, $sensitiveMessage);

    expect($sanitized)->not->toContain('root');
    expect($sanitized)->toContain('[redacted]');
});

test('it detects requirements', function () {
    $reflection = new \ReflectionClass(EnvAuditor::class);
    $method = $reflection->getMethod('checkRequirements');
    $method->setAccessible(true);

    $results = $method->invoke($this->auditor);

    expect($results)->toBeArray();
    expect($results[0]['name'])->toContain('PHP Version');
});
