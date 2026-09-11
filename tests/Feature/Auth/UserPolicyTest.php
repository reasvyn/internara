<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('T4B26: UserPolicy', function () {
    test('T4B26-FR-RBAC-015: admin allowed on viewAny, create, and lifecycle dashboard; student denied', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);
        expect(Gate::allows('viewAny', User::class))->toBeTrue()
            ->and(Gate::allows('create', User::class))->toBeTrue()
            ->and(Gate::allows('viewLifecycleDashboard', User::class))->toBeTrue()
            ->and(Gate::allows('viewAdmin', User::class))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($student);
        expect(Gate::allows('viewAny', User::class))->toBeFalse()
            ->and(Gate::allows('create', User::class))->toBeFalse()
            ->and(Gate::allows('viewLifecycleDashboard', User::class))->toBeFalse()
            ->and(Gate::allows('viewAdmin', User::class))->toBeFalse();
    });

    test('T4B26-FR-RBAC-015: user can view self; admin can view others; student cannot view others', function () {
        $me = User::factory()->create();
        $me->assignRole('student');
        $other = User::factory()->create();

        $this->actingAs($me);
        expect(Gate::allows('view', $me))->toBeTrue()
            ->and(Gate::allows('view', $other))->toBeFalse();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $other))->toBeTrue();
    });

    test('T4B26-FR-RBAC-015: user can update self; admin can update others', function () {
        $me = User::factory()->create();
        $me->assignRole('student');

        $this->actingAs($me);
        expect(Gate::allows('update', $me))->toBeTrue();

        $other = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $other))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('update', $other))->toBeFalse();
    });

    test('T4B26-FR-RBAC-015: super_admin target can only be updated by self', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $superadmin))->toBeFalse();

        $this->actingAs($superadmin);
        expect(Gate::allows('update', $superadmin))->toBeTrue();
    });

    test('T4B26-FR-RBAC-015: admin can delete others but never self or super_admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $other = User::factory()->create();
        $protected = User::factory()->create();
        $protected->assignRole('superadmin');

        $this->actingAs($admin);
        expect(Gate::allows('delete', $other))->toBeTrue()
            ->and(Gate::allows('delete', $admin))->toBeFalse()
            ->and(Gate::allows('delete', $protected))->toBeFalse()
            ->and(Gate::allows('restore', $protected))->toBeFalse()
            ->and(Gate::allows('forceDelete', $protected))->toBeFalse()
            ->and(Gate::allows('lockAccount', $protected))->toBeFalse()
            ->and(Gate::allows('unlockAccount', $protected))->toBeFalse();
    });

    test('T4B26-FR-RBAC-015: admin can restore, force-delete, lock, and unlock ordinary users; student cannot', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $other = User::factory()->create();

        $this->actingAs($admin);
        expect(Gate::allows('restore', $other))->toBeTrue()
            ->and(Gate::allows('lockAccount', $other))->toBeTrue()
            ->and(Gate::allows('unlockAccount', $other))->toBeTrue();

        $victim = User::factory()->create();
        expect(Gate::allows('forceDelete', $victim))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('delete', $other))->toBeFalse()
            ->and(Gate::allows('restore', $other))->toBeFalse()
            ->and(Gate::allows('lockAccount', $other))->toBeFalse()
            ->and(Gate::allows('unlockAccount', $other))->toBeFalse();
    });

    test('T4B26-FR-RBAC-010: superadmin bypasses gates via before()', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $other = User::factory()->create();

        $this->actingAs($superadmin);
        expect(Gate::allows('viewAny', User::class))->toBeTrue()
            ->and(Gate::allows('delete', $other))->toBeTrue();
    });
});
