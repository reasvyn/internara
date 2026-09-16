<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('7H5D6: document authorization', function () {
    test('7H5D6-FR-OFFD-021: only admins may create, update, or delete official document records', function (): void {
        $document = Document::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', Document::class))->toBeTrue()
            ->and(Gate::allows('update', $document))->toBeTrue()
            ->and(Gate::allows('delete', $document))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', Document::class))->toBeFalse()
            ->and(Gate::allows('update', $document))->toBeFalse()
            ->and(Gate::allows('delete', $document))->toBeFalse();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Document::class))->toBeFalse()
            ->and(Gate::allows('update', $document))->toBeFalse()
            ->and(Gate::allows('delete', $document))->toBeFalse();
    });

    test('7H5D6-FR-OFFD-021: consent uploads belong to students while verification belongs to admins', function (): void {
        $record = RegistrationDocument::factory()->create();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', RegistrationDocument::class))->toBeTrue()
            ->and(Gate::allows('update', $record))->toBeFalse();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', RegistrationDocument::class))->toBeFalse();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $record))->toBeTrue()
            ->and(Gate::allows('delete', $record))->toBeTrue();
    });

    test('7H5D6-FR-OFFD-019: consent reads stay scoped to the owning student and admins', function (): void {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $registration = Registration::factory()->create(['student_id' => $owner->id]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $owner->id,
        ]);
        $record = RegistrationDocument::factory()->create(['registration_id' => $registration->id]);

        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $mentor = User::factory()->create();
        $mentor->assignRole('teacher');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);
        expect(Gate::allows('view', $record))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $record))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $record))->toBeFalse();

        $this->actingAs($mentor);
        expect(Gate::allows('view', $record))->toBeFalse();
    });
});
