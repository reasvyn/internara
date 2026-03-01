<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\User\Services\Contracts\UserService;

uses(RefreshDatabase::class);

test('concurrent enrollment audit: only one student can take the last slot', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(InternshipPlacementService::class)
        ->factory()
        ->create([
            'internship_id' => $program->id,
            'capacity_quota' => 1,
        ]);

    $student1 = app(UserService::class)->factory()->create();
    $student2 = app(UserService::class)->factory()->create();
    $teacher = app(UserService::class)->factory()->create();

    $data1 = [
        'internship_id' => $program->id,
        'placement_id' => $placement->id,
        'student_id' => $student1->id,
        'teacher_id' => $teacher->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(3)->toDateString(),
    ];

    $data2 = $data1;
    $data2['student_id'] = $student2->id;

    // Simulate rapid sequential requests (closest to concurrency in standard Pest)
    $service = app(RegistrationService::class);

    // First one should succeed
    $service->register($data1);

    // Second one should fail
    expect(fn () => $service->register($data2))->toThrow(
        \Modules\Exception\AppException::class,
        'internship::exceptions.no_slots_available',
    );
});
