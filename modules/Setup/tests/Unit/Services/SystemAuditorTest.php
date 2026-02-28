<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\File;
use Modules\Setup\Services\SystemAuditor;

describe('SystemAuditor Unit Test', function () {
    
    beforeEach(function () {
        $this->auditor = new SystemAuditor();
    });

    test('it can perform a full system audit', function () {
        $audit = $this->auditor->audit();

        expect($audit)->toBeArray()
            ->toHaveKeys(['requirements', 'permissions', 'database']);
    });

    test('it validates mandatory php requirements', function () {
        $requirements = $this->auditor->checkRequirements();

        expect($requirements)->toBeArray()
            ->toHaveKey('php_version')
            ->toHaveKey('extension_bcmath');
        
        expect($requirements['php_version'])->toBeTrue();
    });

    test('it audits directory write permissions', function () {
        $permissions = $this->auditor->checkPermissions();

        expect($permissions)->toBeArray()
            ->toHaveKey('storage_directory')
            ->toHaveKey('bootstrap_cache');
        
        // In local development/CI, these should be true
        expect($permissions['storage_directory'])->toBeTrue();
    });

    test('it audits database connectivity', function () {
        $dbStatus = $this->auditor->checkDatabase();

        expect($dbStatus)->toBeArray()
            ->toHaveKey('connection')
            ->toHaveKey('message');
            
        expect($dbStatus['connection'])->toBeTrue();
    });

    test('it passes when all environment criteria are met', function () {
        expect($this->auditor->passes())->toBeTrue();
    });

    describe('Failure Scenarios', function () {
        test('it fails audit when a core permission is missing', function () {
            // We create a partial mock of the auditor to simulate a permission failure
            $partialAuditor = \Mockery::mock(SystemAuditor::class)->makePartial();
            $partialAuditor->shouldReceive('checkPermissions')
                ->andReturn(['storage_directory' => false]);
            
            expect($partialAuditor->passes())->toBeFalse();
        });

        test('it fails audit when database connection is lost', function () {
            $partialAuditor = \Mockery::mock(SystemAuditor::class)->makePartial();
            $partialAuditor->shouldReceive('checkDatabase')
                ->andReturn(['connection' => false, 'message' => 'Connection refused']);
            
            expect($partialAuditor->passes())->toBeFalse();
        });
    });
});
