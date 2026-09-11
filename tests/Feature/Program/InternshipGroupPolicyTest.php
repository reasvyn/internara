<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('IT0OE: InternshipGroupPolicy', function () {
    test('IT0OE-FR-GROUP-024: listing and viewing are open to every authenticated user', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $group = InternshipGroup::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', InternshipGroup::class))->toBeTrue()
            ->and(Gate::allows('view', $group))->toBeTrue();
    });

    test('IT0OE-FR-GROUP-024: listing and viewing are open to guests', function () {
        $group = InternshipGroup::factory()->create();

        expect(Gate::allows('viewAny', InternshipGroup::class))->toBeTrue()
            ->and(Gate::allows('view', $group))->toBeTrue();
    });

    test('IT0OE-FR-GROUP-024: writes reserved to admin', function () {
        $group = InternshipGroup::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', InternshipGroup::class))->toBeTrue()
            ->and(Gate::allows('update', $group))->toBeTrue()
            ->and(Gate::allows('delete', $group))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', InternshipGroup::class))->toBeFalse()
            ->and(Gate::allows('update', $group))->toBeFalse()
            ->and(Gate::allows('delete', $group))->toBeFalse();
    });

    test('T4B26-FR-RBAC-010: superadmin bypasses via before()', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $group = InternshipGroup::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('create', InternshipGroup::class))->toBeTrue()
            ->and(Gate::allows('delete', $group))->toBeTrue();
    });
});
