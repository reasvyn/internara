<?php

declare(strict_types=1);

use App\Actions\Internship\RegisterInternshipAction;
use App\Models\Registration;
use Database\Factories\InternshipFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('registers a student for an internship', function () {
        $student = UserFactory::new()->create()->assignRole('student');
        $internship = InternshipFactory::new()->create();

        $registration = app(RegisterInternshipAction::class)->execute($student, [
            'internship_id' => $internship->id,
            'academic_year' => '2025/2026',
        ]);

        expect($registration)->toBeInstanceOf(Registration::class)
            ->and($registration->internship_id)->toBe($internship->id);
    });

    it('throws RuntimeException if student already has active registration', function () {
        $student = UserFactory::new()->create()->assignRole('student');
        $internship = InternshipFactory::new()->create();

        app(RegisterInternshipAction::class)->execute($student, [
            'internship_id' => $internship->id,
        ]);

        expect(fn () => app(RegisterInternshipAction::class)->execute($student, [
            'internship_id' => $internship->id,
        ]))->toThrow(RuntimeException::class, 'already has an active or pending');
    });
});
