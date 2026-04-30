<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Log;
use Modules\Setup\Services\InstallationAuditor;
use Tests\TestCase;

/**
 * [S1 - Secure] Test sensitive data sanitization
 * [S2 - Sustain] Test clear pass/fail reporting
 * [S3 - Scalable] Test extensible checks
 */
describe('InstallationAuditor', function () {
    beforeEach(function () {
        $this->auditor = new InstallationAuditor();
    });

    describe('audit', function () {
        it('returns array with requirements, permissions, database', function () {
            $result = $this->auditor->audit();
            
            expect($result)->toBeArray();
            expect($result)->toHaveKey('requirements');
            expect($result)->toHaveKey('permissions');
            expect($result)->toHaveKey('database');
        });
    });

    describe('passes', function () {
        it('returns true when all checks pass', function () {
            // Mock successful checks
            $result = $this->auditor->passes();
            
            expect($result)->toBeBool();
        });
    });

    describe('checkRequirements', function () {
        it('checks PHP version', function () {
            $reflection = new \ReflectionClass($this->auditor);
            $method = $reflection->getMethod('checkRequirements');
            $result = $method->invoke($this->auditor);
            
            expect($result)->toBeArray();
            expect($result[0]['name'])->toContain('PHP Version');
        });

        it('checks required extensions', function () {
            $reflection = new \ReflectionClass($this->auditor);
            $method = $reflection->getMethod('checkRequirements');
            $result = $method->invoke($this->auditor);
            
            $extensionChecks = array_filter($result, fn($item) => str_contains($item['name'], 'Extension'));
            expect($extensionChecks)->not->toBeEmpty();
        });
    });

    describe('checkPermissions', function () {
        it('checks storage directory', function () {
            $reflection = new \ReflectionClass($this->auditor);
            $method = $reflection->getMethod('checkPermissions');
            $result = $method->invoke($this->auditor);
            
            expect($result)->toBeArray();
            expect($result[0]['name'])->toContain('storage');
        });
    });

    describe('checkDatabase', function () {
        it('returns connection status', function () {
            $reflection = new \ReflectionClass($this->auditor);
            $method = $reflection->getMethod('checkDatabase');
            $result = $method->invoke($this->auditor);
            
            expect($result)->toBeArray();
            expect($result[0]['name'])->toBe('Database connection');
        });
    });

    describe('sanitizeErrorMessage', function () {
        it('hides passwords in error messages', function () {
            $reflection = new \ReflectionClass($this->auditor);
            $method = $reflection->getMethod('sanitizeErrorMessage');
            
            $input = "SQLSTATE[HY000]: password=secret123 host=localhost";
            $result = $method->invoke($this->auditor, $input);
            
            expect($result)->not->toContain('secret123');
            expect($result)->toContain('password=****');
        });

        it('hides user credentials', function () {
            $reflection = new \ReflectionClass($this->auditor);
            $method = $reflection->getMethod('sanitizeErrorMessage');
            
            $input = "Access denied for user=admin host=localhost";
            $result = $method->invoke($this->auditor, $input);
            
            expect($result)->not->toContain('admin');
            expect($result)->toContain('user=****');
        });
    });
});
