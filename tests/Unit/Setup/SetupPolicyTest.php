<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Models\Setup;
use App\Domain\Setup\Policies\SetupPolicy;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->policy = new SetupPolicy;

    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('SetupPolicy', function () {
    it('allows admin to view any', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('denies student to view any', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        expect($this->policy->viewAny($user))->toBeFalse();
    });

    it('allows admin to view', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $setup = Setup::factory()->create();

        expect($this->policy->view($user, $setup))->toBeTrue();
    });

    it('denies create to everyone', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        expect($this->policy->create($user))->toBeFalse();
    });

    it('allows admin to update', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $setup = Setup::factory()->create();

        expect($this->policy->update($user, $setup))->toBeTrue();
    });

    it('denies delete to everyone', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        expect($this->policy->delete($user, $setup = Setup::factory()->create()))->toBeFalse();
    });
});
