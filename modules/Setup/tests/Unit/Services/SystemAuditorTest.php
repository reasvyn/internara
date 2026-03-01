<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Setup\Services\SystemAuditor;

describe('SystemAuditor Unit Test', function () {
    beforeEach(function () {
        $this->service = new SystemAuditor();
    });

    test('it can perform a full system audit', function () {
        $dbConnection = \Mockery::mock(\Illuminate\Database\ConnectionInterface::class);
        $dbConnection->shouldReceive('getPdo')->andReturn(true);
        DB::shouldReceive('connection')->andReturn($dbConnection);
        File::shouldReceive('exists')->andReturn(true);

        $results = $this->service->audit();

        expect($results)->toHaveKeys(['requirements', 'permissions', 'database']);
    });

    test('it validates mandatory php requirements', function () {
        $requirements = $this->service->checkRequirements();

        expect($requirements)->toBeArray()
            ->and($requirements['php_version'])->toBeTrue();
    });

    test('it audits directory write permissions', function () {
        File::shouldReceive('exists')->andReturn(true);

        $permissions = $this->service->checkPermissions();

        expect($permissions)->toBeArray()
            ->toHaveKeys(['storage_directory', 'storage_logs', 'storage_framework', 'bootstrap_cache', 'env_file']);
    });

    test('it audits database connectivity', function () {
        $dbConnection = \Mockery::mock(\Illuminate\Database\ConnectionInterface::class);
        $dbConnection->shouldReceive('getPdo')->andReturn(true);
        DB::shouldReceive('connection')->andReturn($dbConnection);

        $dbStatus = $this->service->checkDatabase();

        expect($dbStatus['connection'])->toBeTrue();
    });

    test('it passes when all environment criteria are met', function () {
        // Use a partial mock to bypass the actual file system and DB calls
        $partialService = \Mockery::mock(SystemAuditor::class)->makePartial();
        $partialService->shouldReceive('checkRequirements')->andReturn(['php_version' => true]);
        $partialService->shouldReceive('checkPermissions')->andReturn(['env_file' => true]);
        $partialService->shouldReceive('checkDatabase')->andReturn(['connection' => true]);

        expect($partialService->passes())->toBeTrue();
    });

    describe('Failure Scenarios', function () {
        test('it fails audit when a requirement is missing', function () {
            $partialService = \Mockery::mock(SystemAuditor::class)->makePartial();
            $partialService->shouldReceive('checkRequirements')->andReturn(['php_version' => false]);
            $partialService->shouldReceive('checkPermissions')->andReturn(['env_file' => true]);
            $partialService->shouldReceive('checkDatabase')->andReturn(['connection' => true]);

            expect($partialService->passes())->toBeFalse();
        });

        test('it fails audit when database connection fails', function () {
            DB::shouldReceive('connection')->andThrow(new \Exception('Connection failed'));

            $dbStatus = $this->service->checkDatabase();

            expect($dbStatus['connection'])->toBeFalse()
                ->and($dbStatus['message'])->toBe('Connection failed');
        });
    });
});
