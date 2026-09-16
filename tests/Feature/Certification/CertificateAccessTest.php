<?php

declare(strict_types=1);

use App\Modules\Certification\Domain\Certificate\Livewire\CertificateList;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function j0m04bAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

function j0m04bStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

function j0m04bRegistrationFor(User $owner): Registration
{
    $registration = Registration::factory()->create();
    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'user_id' => $owner->id,
    ]);

    return $registration;
}

describe('J0M04: certificate access and retrieval', function (): void {
    test('J0M04-FR-CERT-004: only active templates are offered for new issuance', function (): void {
        $admin = j0m04bAdmin();
        $active = CertificateTemplate::factory()->create(['name' => 'ActiveTpl-'.uniqid(), 'is_active' => true]);
        $retired = CertificateTemplate::factory()->create(['name' => 'RetiredTpl-'.uniqid(), 'is_active' => false]);

        Livewire::actingAs($admin)->test(CertificateList::class)
            ->assertSee($active->name)
            ->assertDontSee($retired->name);

        expect(CertificateTemplate::find($retired->id))->not->toBeNull();
    });

    test('J0M04-FR-CERT-019: certificate reads are role-gated by ownership', function (): void {
        $owner = j0m04bStudent();
        $outsider = j0m04bStudent();
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $admin = j0m04bAdmin();
        $registration = j0m04bRegistrationFor($owner);
        $certificate = Certificate::factory()->create(['registration_id' => $registration->id]);

        $this->actingAs($admin);
        expect(Gate::allows('view', $certificate))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $certificate))->toBeTrue()
            ->and(Gate::allows('viewAny', Certificate::class))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $certificate))->toBeFalse();

        $this->actingAs($teacher);
        expect(Gate::allows('viewAny', Certificate::class))->toBeFalse()
            ->and(Gate::allows('view', $certificate))->toBeFalse();
    });

    test('J0M04-FR-CERT-019: only admins may issue or revoke; certificates are never updated or deleted', function (): void {
        $admin = j0m04bAdmin();
        $student = j0m04bStudent();
        $certificate = Certificate::factory()->create();

        $this->actingAs($admin);
        expect(Gate::allows('create', Certificate::class))->toBeTrue()
            ->and(Gate::allows('revoke', $certificate))->toBeTrue()
            ->and(Gate::allows('update', $certificate))->toBeFalse()
            ->and(Gate::allows('delete', $certificate))->toBeFalse();

        $this->actingAs($student);
        expect(Gate::allows('create', Certificate::class))->toBeFalse()
            ->and(Gate::allows('revoke', $certificate))->toBeFalse();
    });
});
