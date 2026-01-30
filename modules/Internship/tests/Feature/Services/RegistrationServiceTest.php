<?php

declare(strict_types=1);

use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\User\Services\Contracts\UserService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('it can register a student for a placement if capacity is available', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create([
            'internship_id' => $program->id,
            'capacity_quota' => 1,
        ]);
    $student = app(UserService::class)->factory()->create();
    $teacher = app(UserService::class)->factory()->create();

    $registration = app(RegistrationService::class)->register([
        'internship_id' => $program->id,
        'placement_id' => $placement->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(3)->toDateString(),
    ]);

    expect($registration)
        ->toBeInstanceOf(InternshipRegistration::class)
        ->and($registration->student_id)
        ->toBe($student->id)
        ->and($registration->teacher_id)
        ->toBe($teacher->id);
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
    $teacher = app(UserService::class)->factory()->create();

    $data = [
        'internship_id' => $program->id,
        'placement_id' => $placement1->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(3)->toDateString(),
    ];

    // First registration
    app(RegistrationService::class)->register($data);

    // Second registration for same program but different placement
    $data['placement_id'] = $placement2->id;
    expect(fn () => app(RegistrationService::class)->register($data))->toThrow(
        AppException::class,
        'internship::exceptions.student_already_registered',
    );
});

test('it throws exception if no capacity available', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
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

    // Fill the only slot
    app(RegistrationService::class)->register($data1);

    // Try to register another student
    $data2 = $data1;
    $data2['student_id'] = $student2->id;
    expect(fn () => app(RegistrationService::class)->register($data2))->toThrow(
        AppException::class,
        'internship::exceptions.no_slots_available',
    );
});

test('it logs the placement assignment when registering', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create([
            'internship_id' => $program->id,
            'capacity_quota' => 5,
        ]);
    $student = app(UserService::class)->factory()->create();
    $teacher = app(UserService::class)->factory()->create();

    $registration = app(RegistrationService::class)->register([
        'internship_id' => $program->id,
        'placement_id' => $placement->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(3)->toDateString(),
    ]);

    $this->assertDatabaseHas('internship_placement_history', [
        'registration_id' => $registration->id,
        'placement_id' => $placement->id,
        'action' => 'assigned',
    ]);
});

test('it can reassign a placement and logs the change', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement1 = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create([
            'internship_id' => $program->id,
            'capacity_quota' => 1,
        ]);
    $placement2 = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create([
            'internship_id' => $program->id,
            'capacity_quota' => 1,
        ]);
    $student = app(UserService::class)->factory()->create();
    $teacher = app(UserService::class)->factory()->create();

    $registration = app(RegistrationService::class)->register([
        'internship_id' => $program->id,
        'placement_id' => $placement1->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(3)->toDateString(),
    ]);

    $updatedRegistration = app(RegistrationService::class)->reassignPlacement(
        $registration->id,
        $placement2->id,
        'Moving to a better location',
    );

    expect($updatedRegistration->placement_id)->toBe($placement2->id);

    $this->assertDatabaseHas('internship_placement_history', [
        'registration_id' => $registration->id,
        'placement_id' => $placement2->id,
        'action' => 'changed',
        'reason' => 'Moving to a better location',
    ]);

    // Check metadata
    $history = \Modules\Internship\Models\PlacementHistory::where(
        'registration_id',
        $registration->id,
    )
        ->where('action', 'changed')
        ->first();

    expect($history->metadata['old_placement_id'])
        ->toBe($placement1->id)
        ->and($history->metadata['new_placement_id'])
        ->toBe($placement2->id);
});

test('it enforces advisor invariant', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create(['internship_id' => $program->id]);
    $student = app(UserService::class)->factory()->create();

    expect(
        fn () => app(RegistrationService::class)->register([
            'internship_id' => $program->id,
            'placement_id' => $placement->id,
            'student_id' => $student->id,
            // 'teacher_id' missing
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]),
    )->toThrow(AppException::class, 'internship::exceptions.advisor_required_for_placement');
});

test('it enforces temporal integrity', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create(['internship_id' => $program->id]);
    $student = app(UserService::class)->factory()->create();
    $teacher = app(UserService::class)->factory()->create();

    // Missing dates
    expect(
        fn () => app(RegistrationService::class)->register([
            'internship_id' => $program->id,
            'placement_id' => $placement->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ]),
    )->toThrow(AppException::class, 'internship::exceptions.period_dates_required');

    // Invalid range
    expect(
        fn () => app(RegistrationService::class)->register([
            'internship_id' => $program->id,
            'placement_id' => $placement->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]),
    )->toThrow(AppException::class, 'internship::exceptions.invalid_period_range');
});

test('it restricts registration based on system phase', function () {
    $program = app(\Modules\Internship\Services\Contracts\InternshipService::class)
        ->factory()
        ->create();
    $placement = app(\Modules\Internship\Services\Contracts\InternshipPlacementService::class)
        ->factory()
        ->create(['internship_id' => $program->id]);
    $student = app(UserService::class)->factory()->create();
    $teacher = app(UserService::class)->factory()->create();

    // Change system phase to 'operation'
    setting(['system_phase' => 'operation']);

    expect(
        fn () => app(RegistrationService::class)->register([
            'internship_id' => $program->id,
            'placement_id' => $placement->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]),
    )->toThrow(AppException::class, 'internship::exceptions.registration_closed_for_current_phase');

    // Revert to 'registration' for other tests if necessary (though RefreshDatabase is used)
    setting(['system_phase' => 'registration']);
});
