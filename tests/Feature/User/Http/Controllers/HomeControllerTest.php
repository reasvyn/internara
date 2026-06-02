<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('HomeController', function () {
    it('redirects to setup when not installed', function () {
        Setup::first()?->delete();

        $this->get(route('home'))
            ->assertRedirect(route('setup'));
    });

    it('redirects to login when not authenticated', function () {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    });

    it('redirects to dashboard when authenticated', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    });
});
