<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('MBB5R: RegistrationDocumentPolicy', function () {
    test('MBB5R-FR-REG-025: viewAny reserved to admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('viewAny', RegistrationDocument::class))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', RegistrationDocument::class))->toBeFalse();
    });

    test('MBB5R-FR-REG-025: allows admin and owning student on view, denies outsider', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $registration = Registration::factory()->create(['student_id' => $owner->id]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $owner->id,
        ]);
        $document = RegistrationDocument::factory()->create(['registration_id' => $registration->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $document))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $document))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $document))->toBeFalse();
    });

    test('MBB5R-FR-REG-025: student uploads, teacher cannot create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', RegistrationDocument::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', RegistrationDocument::class))->toBeFalse();
    });

    test('MBB5R-FR-REG-025: update and delete reserved to admin', function () {
        $document = RegistrationDocument::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $document))->toBeTrue()
            ->and(Gate::allows('delete', $document))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('update', $document))->toBeFalse()
            ->and(Gate::allows('delete', $document))->toBeFalse();
    });
});
