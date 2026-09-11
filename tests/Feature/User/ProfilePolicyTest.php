<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('95EVB: ProfilePolicy', function () {
    test('95EVB-FR-USER-032: viewAny reserved to admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('viewAny', Profile::class))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', Profile::class))->toBeFalse();
    });

    test('95EVB-FR-USER-032: admin and owning user can view, outsider cannot', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $profile = Profile::factory()->create(['user_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $profile))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $profile))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $profile))->toBeFalse();
    });

    test('95EVB-FR-USER-032: admin and owning user can update, outsider cannot', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $profile = Profile::factory()->create(['user_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $profile))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('update', $profile))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('update', $profile))->toBeFalse();
    });

    test('T4B26-FR-RBAC-010: superadmin bypasses via before()', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $profile = Profile::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('view', $profile))->toBeTrue()
            ->and(Gate::allows('update', $profile))->toBeTrue();
    });
});
