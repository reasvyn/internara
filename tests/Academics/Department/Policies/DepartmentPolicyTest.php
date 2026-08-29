<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Academics\Domain\Department\Policies\DepartmentPolicy;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 4HWSB — Department Management — DepartmentPolicy (spec-driven)
|--------------------------------------------------------------------------
*/

describe('4HWSB: DepartmentPolicy', function (): void {
    beforeEach(function (): void {
        $this->policy = new DepartmentPolicy;
    });

    test('4HWSB-FR-DM24: viewAny() returns true for all authenticated users', function (): void {
        $user = User::factory()->create();

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    test('4HWSB-FR-DM24: viewAny() returns true for guest (null user)', function (): void {
        expect($this->policy->viewAny(null))->toBeTrue();
    });

    test('4HWSB-FR-DM25: view() returns true for all authenticated users', function (): void {
        $user = User::factory()->create();
        $dept = Department::factory()->create();

        expect($this->policy->view($user, $dept))->toBeTrue();
    });

    test('4HWSB-FR-DM26: create() requires admin role', function (): void {
        $admin = User::factory()->create()->assignRole('admin');
        $nonAdmin = User::factory()->create();

        expect($this->policy->create($admin))->toBeTrue()
            ->and($this->policy->create($nonAdmin))->toBeFalse();
    });

    test('4HWSB-FR-DM27: update() requires admin role', function (): void {
        $admin = User::factory()->create()->assignRole('admin');
        $nonAdmin = User::factory()->create();
        $dept = Department::factory()->create();

        expect($this->policy->update($admin, $dept))->toBeTrue()
            ->and($this->policy->update($nonAdmin, $dept))->toBeFalse();
    });

    test('4HWSB-FR-DM28: delete() requires admin AND canBeDeleted()', function (): void {
        $admin = User::factory()->create()->assignRole('admin');
        $nonAdmin = User::factory()->create()->assignRole('admin');
        $deptWithProfiles = Department::factory()->create();
        $deptWithoutProfiles = Department::factory()->create();

        // Create a profile for deptWithProfiles
        Profile::factory()->create(['department_id' => $deptWithProfiles->id]);

        // Admin can delete dept without profiles
        expect($this->policy->delete($admin, $deptWithoutProfiles))->toBeTrue();

        // Admin CANNOT delete dept with profiles (canBeDeleted() returns false)
        expect($this->policy->delete($admin, $deptWithProfiles))->toBeFalse();

        // Non-admin cannot delete even without profiles
        $regularUser = User::factory()->create();
        expect($this->policy->delete($regularUser, $deptWithoutProfiles))->toBeFalse();
    });

    test('4HWSB-FR-DM29: forceDelete() always returns false', function (): void {
        $admin = User::factory()->create()->assignRole('admin');
        $dept = Department::factory()->create();

        expect($this->policy->forceDelete($admin, $dept))->toBeFalse();
    });
});
