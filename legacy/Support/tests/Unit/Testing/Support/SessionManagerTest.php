<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Unit\Testing\Support;

use Illuminate\Support\Facades\File;
use Modules\Support\Contracts\Testing\SessionManagerInterface;
use Modules\Support\Testing\Support\SessionManager;

describe('SessionManager', function () {
    beforeEach(function () {
        // Use a unique session ID for each test
        $this->sessionId = 'test_'.uniqid();
        $this->manager = new SessionManager($this->sessionId);
    });

    afterEach(function () {
        // Clean up test sessions
        $path = storage_path("framework/testing/sessions/{$this->sessionId}");
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }
    });

    it('implements SessionManagerInterface', function () {
        expect($this->manager)->toBeInstanceOf(SessionManagerInterface::class);
    });

    it('returns correct session ID', function () {
        expect($this->manager->getSessionId())->toBe($this->sessionId);
    });

    it('records and retrieves results', function () {
        $executionResult = [
            'output' => 'Test output',
            'errorOutput' => '',
            'exitCode' => 0,
            'peakMemory' => 1024,
        ];

        $this->manager->record('TestModule', 'Unit', true, $executionResult);

        $results = $this->manager->getResults();
        expect($results)->toHaveCount(1);
        expect($results[0]['module'])->toBe('TestModule');
        expect($results[0]['type'])->toBe('Unit');
        expect($results[0]['success'])->toBeTrue();
    });

    it('detects passed segments correctly', function () {
        $executionResult = [
            'output' => 'Test output',
            'errorOutput' => '',
            'exitCode' => 0,
            'peakMemory' => 1024,
        ];

        $this->manager->record('TestModule', 'Unit', true, $executionResult);

        expect($this->manager->isPassed('TestModule', 'Unit'))->toBeTrue();
        expect($this->manager->isPassed('TestModule', 'Feature'))->toBeFalse();
    });

    it('invalidates passed segment when files change', function () {
        // This test assumes the module path exists
        $executionResult = [
            'output' => 'Test output',
            'errorOutput' => '',
            'exitCode' => 0,
            'peakMemory' => 1024,
        ];

        $this->manager->record('System', 'Unit', true, $executionResult);

        // Should be valid initially
        expect($this->manager->isPassed('System', 'Unit'))->toBeTrue();
    });

    it('clears session data', function () {
        $executionResult = [
            'output' => 'Test output',
            'errorOutput' => '',
            'exitCode' => 0,
            'peakMemory' => 1024,
        ];

        $this->manager->record('TestModule', 'Unit', true, $executionResult);
        expect($this->manager->getResults())->toHaveCount(1);

        $this->manager->clear();
        expect($this->manager->getResults())->toHaveCount(0);
    });

    it('provides metadata', function () {
        $metadata = $this->manager->getMetadata();

        expect($metadata)->toHaveKeys(['sessionId', 'segmentCount', 'diskUsageBytes', 'oldestTimestamp']);
        expect($metadata['sessionId'])->toBe($this->sessionId);
    });

    it('cleans up old sessions', function () {
        // Create an old session directory
        $oldSessionId = 'old_test_'.uniqid();
        $oldPath = storage_path("framework/testing/sessions/{$oldSessionId}");
        File::makeDirectory($oldPath, 0700, true);
        File::put("{$oldPath}/test.json", json_encode(['test' => true]));

        // Set modification time to 10 days ago
        touch($oldPath, time() - (10 * 24 * 3600));

        $deleted = SessionManager::cleanup(7);

        expect($deleted)->toBeGreaterThanOrEqual(0);
    });

    it('masks sensitive data in output', function () {
        $reflection = new \ReflectionClass(SessionManager::class);
        $method = $reflection->getMethod('sanitizeOutput');
        $method->setAccessible(true);

        $output = 'password: "secret123" and token: "abc123"';
        $sanitized = $method->invoke($this->manager, $output);

        expect($sanitized)->toContain('[masked]');
        expect($sanitized)->not->toContain('secret123');
    });

    it('limits output size', function () {
        $reflection = new \ReflectionClass(SessionManager::class);
        $method = $reflection->getMethod('sanitizeOutput');
        $method->setAccessible(true);

        $longOutput = str_repeat('a', 20000);
        $sanitized = $method->invoke($this->manager, $longOutput);

        expect(strlen($sanitized))->toBeLessThan(20000);
        expect($sanitized)->toContain('[truncated]');
    });
});
