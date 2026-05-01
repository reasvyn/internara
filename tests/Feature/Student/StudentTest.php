<?php

declare(strict_types=1);

use App\Actions\Internship\RegisterInternshipAction;
use App\Enums\InternshipStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Internship;
use App\Models\InternshipRegistration;
use App\Models\User;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    $this->student = User::factory()->create();
    $this->student->assignRole(RoleEnum::STUDENT);
});

describe('Student Registration', function () {
    it('can access registration page', function () {
        actingAs($this->student);

        $this->get(route('student.internships.register'))
            ->assertOk();
    });

    it('can register for internship', function () {
        $internship = Internship::factory()->create([
            'status' => InternshipStatus::ACTIVE->value,
        ]);

        $action = app(RegisterInternshipAction::class);

        $registration = $action->execute($this->student, [
            'internship_id' => $internship->id,
            'academic_year' => now()->format('Y'),
        ]);

        expect($registration)->toBeInstanceOf(InternshipRegistration::class)
            ->and($registration->student_id)->toBe($this->student->id)
            ->and($registration->internship_id)->toBe($internship->id)
            ->and($registration->latestStatus()?->name)->toBe('pending');
    });

    it('prevents duplicate registration', function () {
        $internship = Internship::factory()->create([
            'status' => InternshipStatus::ACTIVE->value,
        ]);

        $action = app(RegisterInternshipAction::class);

        // First registration
        $action->execute($this->student, [
            'internship_id' => $internship->id,
            'academic_year' => now()->format('Y'),
        ]);

        // Second registration should throw RuntimeException
        expect(fn () => $action->execute($this->student, [
            'internship_id' => $internship->id,
            'academic_year' => now()->format('Y'),
        ]))->toThrow(\RuntimeException::class, 'Student already has an active or pending internship registration.');
    });

    it('prevents registration without active internship', function () {
        $action = app(RegisterInternshipAction::class);

        expect(fn () => $action->execute($this->student, [
            'internship_id' => 'non-existent-id',
            'academic_year' => now()->format('Y'),
        ]))->toThrow(\Exception::class);
    });
});
