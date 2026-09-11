<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('CertificatePolicy', function () {
    test('allows admin-group and student on viewAny, denies teacher', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', Certificate::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('viewAny', Certificate::class))->toBeFalse();
    });

    test('allows admin and owning student on view, denies outsider', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $registration = Registration::factory()->create(['student_id' => $owner->id]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $owner->id,
        ]);
        $certificate = Certificate::factory()->create(['registration_id' => $registration->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $certificate))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $certificate))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $certificate))->toBeFalse();
    });

    test('allows admin on create and revoke, denies student', function () {
        $certificate = Certificate::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', Certificate::class))->toBeTrue()
            ->and(Gate::allows('revoke', $certificate))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Certificate::class))->toBeFalse()
            ->and(Gate::allows('revoke', $certificate))->toBeFalse();
    });

    test('denies update and delete even for admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $certificate = Certificate::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('update', $certificate))->toBeFalse()
            ->and(Gate::allows('delete', $certificate))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', Certificate::class))->toBeTrue()
            ->and(Gate::allows('create', Certificate::class))->toBeTrue();
    });
});
