<?php

declare(strict_types=1);

namespace Modules\School\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService;

uses(RefreshDatabase::class);

test('it can retrieve school instance', function () {
    $school = School::factory()->create(['name' => 'SMK Internara']);
    $service = app(SchoolService::class);

    $result = $service->getSchool();

    expect($result)->toBeInstanceOf(School::class)->and($result->name)->toBe('SMK Internara');
});
