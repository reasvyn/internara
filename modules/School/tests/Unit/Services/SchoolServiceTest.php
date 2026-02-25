<?php

declare(strict_types=1);

namespace Modules\School\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Exception\AppException;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService;

uses(RefreshDatabase::class);

describe('School Service', function () {
    test('it can retrieve school instance', function () {
        $school = School::factory()->create(['name' => 'SMK Internara']);
        $service = app(SchoolService::class);

        $result = $service->getSchool();

        expect($result)->toBeInstanceOf(School::class)
            ->and($result->name)->toBe('SMK Internara');
    });

    test('it enforces authorization for school creation [SYRS-NF-502]', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('create', School::class)
            ->andThrow(\Illuminate\Auth\Access\AuthorizationException::class);

        $service = app(SchoolService::class);
        $service->create(['name' => 'Unauthorized School']);
    })->throws(\Illuminate\Auth\Access\AuthorizationException::class);

    test('it validates NPSN format (must be 8 digits) [ARC01-INST-01]', function () {
        Gate::shouldReceive('authorize')->andReturn(true);
        $service = app(SchoolService::class);
        
        $service->create(['name' => 'Test School', 'npsn' => '123']);
    })->throws(AppException::class);
});
