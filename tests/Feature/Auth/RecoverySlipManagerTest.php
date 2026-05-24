<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Auth\Livewire\RecoveryCode;
use App\Domain\Auth\Livewire\RecoverySlipManager;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
});

describe('RecoverySlipManager', function () {
    it('renders the recovery slip manager', function () {
        $admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        Livewire::test(RecoverySlipManager::class)
            ->assertSuccessful();
    });

    it('blocks non-admin users', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(RecoverySlipManager::class)
            ->assertForbidden();
    });

    it('searches users', function () {
        $admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        Livewire::test(RecoverySlipManager::class)
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    });

    it('selects a user', function () {
        $admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        $user = User::factory()->create(['name' => 'Target User']);

        Livewire::test(RecoverySlipManager::class)
            ->set('search', 'Target')
            ->call('selectUser', $user->id)
            ->assertSet('selectedUser.id', $user->id);
    });

    it('generates recovery codes for selected user', function () {
        $admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        $user = User::factory()->create();

        Livewire::test(RecoverySlipManager::class)
            ->call('selectUser', $user->id)
            ->call('generate')
            ->assertSet('generatedCode', fn ($codes) => is_array($codes) && count($codes) > 0);
    });

    it('resets form after generation', function () {
        $admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        $user = User::factory()->create();

        Livewire::test(RecoverySlipManager::class)
            ->call('selectUser', $user->id)
            ->call('generate')
            ->call('resetForm')
            ->assertSet('selectedUser', null)
            ->assertSet('generatedCode', []);
    });
});

describe('RecoveryCode', function () {
    it('generates recovery codes for authenticated user', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(RecoveryCode::class)
            ->call('generate')
            ->assertSet('codes', fn ($codes) => is_array($codes) && count($codes) > 0);
    });

    it('sets codes in session after generation', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(RecoveryCode::class)
            ->call('generate');

        expect(session()->has('recovery_codes'))->toBeTrue();
    });

    it('resets codes', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(RecoveryCode::class)
            ->call('generate')
            ->call('resetCode')
            ->assertSet('codes', []);
    });

    it('clears session on reset', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(RecoveryCode::class)
            ->call('generate')
            ->call('resetCode');

        expect(session()->has('recovery_codes'))->toBeFalse();
    });
});
