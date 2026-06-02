<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('DashboardController', function () {
    it('redirects super admin to admin dashboard', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    });

    it('redirects admin to admin dashboard', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    });

    it('redirects student to student dashboard', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('student.dashboard'));
    });

    it('redirects teacher to teacher dashboard', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('teacher.dashboard'));
    });

    it('redirects supervisor to supervisor dashboard', function () {
        $user = User::factory()->create();
        $user->assignRole('supervisor');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('supervisor.dashboard'));
    });
});
