<?php

declare(strict_types=1);

use App\Auth\AccountRecovery\Actions\GenerateRecoverySlipAction;
use App\Auth\AccountRecovery\Livewire\RecoverySlipManager;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create(['status' => AccountStatus::ACTIVATED]);
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the recovery slip manager component', function () {
    Livewire::test(RecoverySlipManager::class)
        ->assertSuccessful();
});

test('searches for users', function () {
    User::factory()->create(['name' => 'John Doe', 'status' => AccountStatus::ACTIVATED]);

    Livewire::test(RecoverySlipManager::class)
        ->set('search', 'John')
        ->assertSet('search', 'John');
});

test('selects a user for recovery slip generation', function () {
    $target = User::factory()->create(['status' => AccountStatus::ACTIVATED]);

    Livewire::test(RecoverySlipManager::class)
        ->call('selectUser', $target->id)
        ->assertSet('selectedUser.id', $target->id);
});

test('generates recovery codes for selected user', function () {
    $target = User::factory()->create(['status' => AccountStatus::ACTIVATED]);

    Livewire::test(RecoverySlipManager::class)
        ->call('selectUser', $target->id)
        ->call('generate')
        ->assertSet('generatedCode', fn ($codes) => count($codes) === GenerateRecoverySlipAction::CODE_COUNT);
});

test('resets the form after generation', function () {
    $target = User::factory()->create(['status' => AccountStatus::ACTIVATED]);

    Livewire::test(RecoverySlipManager::class)
        ->call('selectUser', $target->id)
        ->call('generate')
        ->call('resetForm')
        ->assertSet('search', '')
        ->assertSet('selectedUser', null)
        ->assertSet('generatedCode', []);
});

test('blocks non-admin access', function () {
    $student = User::factory()->create(['status' => AccountStatus::ACTIVATED]);
    $student->assignRole('student');
    test()->actingAs($student);

    Livewire::test(RecoverySlipManager::class)
        ->assertForbidden();
});
