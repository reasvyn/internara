<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('NTHQA: PartnershipPolicy', function () {
    test('NTHQA-FR-PART-020: admin-group and teachers can list and view; student cannot', function () {
        $partnership = Partnership::factory()->create();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('viewAny', Partnership::class))->toBeTrue()
            ->and(Gate::allows('view', $partnership))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', Partnership::class))->toBeFalse()
            ->and(Gate::allows('view', $partnership))->toBeFalse();
    });

    test('NTHQA-FR-PART-020: all writes reserved to admin', function () {
        $partnership = Partnership::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', Partnership::class))->toBeTrue()
            ->and(Gate::allows('update', $partnership))->toBeTrue()
            ->and(Gate::allows('delete', $partnership))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', Partnership::class))->toBeFalse()
            ->and(Gate::allows('update', $partnership))->toBeFalse()
            ->and(Gate::allows('delete', $partnership))->toBeFalse();
    });

    test('T4B26-FR-RBAC-010: superadmin bypasses via before()', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $partnership = Partnership::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', Partnership::class))->toBeTrue()
            ->and(Gate::allows('delete', $partnership))->toBeTrue();
    });
});
