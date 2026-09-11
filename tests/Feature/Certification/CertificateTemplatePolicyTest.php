<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('CertificateTemplatePolicy', function () {
    test('allows admin on every ability', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $template = CertificateTemplate::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('viewAny', CertificateTemplate::class))->toBeTrue()
            ->and(Gate::allows('view', $template))->toBeTrue()
            ->and(Gate::allows('create', CertificateTemplate::class))->toBeTrue()
            ->and(Gate::allows('update', $template))->toBeTrue()
            ->and(Gate::allows('delete', $template))->toBeTrue();
    });

    test('denies teacher on every ability', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $template = CertificateTemplate::factory()->create();

        $this->actingAs($teacher);

        expect(Gate::allows('viewAny', CertificateTemplate::class))->toBeFalse()
            ->and(Gate::allows('view', $template))->toBeFalse()
            ->and(Gate::allows('create', CertificateTemplate::class))->toBeFalse()
            ->and(Gate::allows('update', $template))->toBeFalse()
            ->and(Gate::allows('delete', $template))->toBeFalse();
    });

    test('denies student on every ability', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $template = CertificateTemplate::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', CertificateTemplate::class))->toBeFalse()
            ->and(Gate::allows('view', $template))->toBeFalse()
            ->and(Gate::allows('create', CertificateTemplate::class))->toBeFalse()
            ->and(Gate::allows('update', $template))->toBeFalse()
            ->and(Gate::allows('delete', $template))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $template = CertificateTemplate::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', CertificateTemplate::class))->toBeTrue()
            ->and(Gate::allows('delete', $template))->toBeTrue();
    });
});
