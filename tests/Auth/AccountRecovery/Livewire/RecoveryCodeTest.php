<?php

declare(strict_types=1);

use App\Auth\AccountRecovery\Actions\GenerateRecoverySlipAction;
use App\Auth\AccountRecovery\Livewire\RecoveryCode;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create(['status' => AccountStatus::ACTIVATED]);
    $user->assignRole('student');
    test()->actingAs($user);
});

test('renders the recovery code component', function () {
    Livewire::test(RecoveryCode::class)
        ->assertSuccessful();
});

test('generates recovery codes', function () {
    Livewire::test(RecoveryCode::class)
        ->call('generate')
        ->assertSet('codes', fn ($codes) => count($codes) === GenerateRecoverySlipAction::CODE_COUNT);
});

test('resets generated codes', function () {
    Livewire::test(RecoveryCode::class)
        ->call('generate')
        ->call('resetCode')
        ->assertSet('codes', [])
        ->assertSet('expiresAt', null);
});

test('redirects to profile when no codes in session', function () {
    Livewire::test(RecoveryCode::class)
        ->call('downloadPdf')
        ->assertRedirect(route('profile'));
});
