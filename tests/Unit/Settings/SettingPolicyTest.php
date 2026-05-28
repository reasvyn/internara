<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Policies\SettingPolicy;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->policy = new SettingPolicy;

    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('SettingPolicy', function () {
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
        $setting = Setting::factory()->create();

        expect($this->policy->view($user, $setting))->toBeTrue();
    });

    it('allows super_admin to create', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        expect($this->policy->create($user))->toBeTrue();
    });

    it('denies admin to create', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($this->policy->create($user))->toBeFalse();
    });

    it('allows super_admin to update', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $setting = Setting::factory()->create();

        expect($this->policy->update($user, $setting))->toBeTrue();
    });

    it('denies admin to update', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $setting = Setting::factory()->create();

        expect($this->policy->update($user, $setting))->toBeFalse();
    });

    it('allows super_admin to delete', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $setting = Setting::factory()->create();

        expect($this->policy->delete($user, $setting))->toBeTrue();
    });

    it('denies admin to delete', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $setting = Setting::factory()->create();

        expect($this->policy->delete($user, $setting))->toBeFalse();
    });
});
