<?php

declare(strict_types=1);

use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;
use Modules\User\Services\Contracts\UserService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('it can register a student for a placement if slots are available', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create([
            'internship_id' => $program->id,
            'slots' => 1,
        ]);
    $student = app(UserService::class)->factory()->create();

    $registration = app(InternshipRegistrationService::class)->register([
        'internship_id' => $program->id,
        'placement_id' => $placement->id,
        'student_id' => $student->id,
    ]);

    expect($registration)
        ->toBeInstanceOf(InternshipRegistration::class)
        ->and($registration->student_id)
        ->toBe($student->id);
});

test('it throws exception if student already registered for the same program', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement1 = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create(['internship_id' => $program->id]);
    $placement2 = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create(['internship_id' => $program->id]);
    $student = app(UserService::class)->factory()->create();

    // First registration
    app(InternshipRegistrationService::class)->register([
        'internship_id' => $program->id,
        'placement_id' => $placement1->id,
        'student_id' => $student->id,
    ]);

    // Second registration for same program but different placement
    expect(
        fn () => app(InternshipRegistrationService::class)->register([
            'internship_id' => $program->id,
            'placement_id' => $placement2->id,
            'student_id' => $student->id,
        ]),
    )->toThrow(AppException::class, 'internship::exceptions.student_already_registered');
});

test('it throws exception if no slots available', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create([
            'internship_id' => $program->id,
            'slots' => 1,
        ]);

    $student1 = app(UserService::class)->factory()->create();
    $student2 = app(UserService::class)->factory()->create();

    // Fill the only slot
    app(InternshipRegistrationService::class)->register([
        'internship_id' => $program->id,
        'placement_id' => $placement->id,
        'student_id' => $student1->id,
    ]);

    // Try to register another student
    expect(
        fn () => app(InternshipRegistrationService::class)->register([
            'internship_id' => $program->id,
            'placement_id' => $placement->id,
            'student_id' => $student2->id,
        ]),
    )->toThrow(AppException::class, 'internship::exceptions.no_slots_available');
});
