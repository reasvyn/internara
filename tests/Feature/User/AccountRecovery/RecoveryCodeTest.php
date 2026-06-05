<?php

declare(strict_types=1);

use App\Domain\User\Aggregates\AccountRecovery\Livewire\RecoveryCode;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('user can generate 10 recovery codes', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    Livewire::actingAs($user)
        ->test(RecoveryCode::class)
        ->call('generate')
        ->assertHasNoErrors()
        ->assertSet('codes', function ($codes) {
            return count($codes) === 10;
        });

    expect(session('recovery_codes'))->toHaveCount(10);
});

test('downloading recovery codes stream PDF works correctly', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    Livewire::actingAs($user)
        ->test(RecoveryCode::class)
        ->call('generate')
        ->call('downloadPdf')
        ->assertFileDownloaded('recovery-codes-'.$user->username.'.pdf');
});

test('downloading without generating codes fails and redirects', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    Livewire::actingAs($user)
        ->test(RecoveryCode::class)
        ->call('downloadPdf')
        ->assertRedirect(route('profile'));
});
