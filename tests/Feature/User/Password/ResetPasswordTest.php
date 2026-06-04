<?php

declare(strict_types=1);

use App\Domain\User\Aggregates\Password\Livewire\ResetPassword;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

// ─── Guest Access ───

test('guest can visit reset password page with token', function () {
    $user = User::factory()->create(['email' => 'alice@example.com']);
    $token = Password::createToken($user);

    $this->get(route('password.reset', $token))
        ->assertStatus(200);
});

test('authenticated user is redirected from reset password page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('password.reset', 'some-token'))
        ->assertRedirect('/dashboard');
});

// ─── Form Validation ───

test('reset password requires email', function () {
    $token = Password::createToken(User::factory()->create());

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('form.password', 'NewPass123')
        ->set('form.password_confirmation', 'NewPass123')
        ->call('resetPassword')
        ->assertHasErrors(['form.email']);
});

test('reset password requires valid email format', function () {
    $token = Password::createToken(User::factory()->create());

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('form.email', 'not-an-email')
        ->set('form.password', 'NewPass123')
        ->set('form.password_confirmation', 'NewPass123')
        ->call('resetPassword')
        ->assertHasErrors(['form.email']);
});

test('reset password requires password', function () {
    $token = Password::createToken(User::factory()->create());

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('form.email', 'anyone@example.com')
        ->call('resetPassword')
        ->assertHasErrors(['form.password']);
});

test('reset password requires password confirmation', function () {
    $token = Password::createToken(User::factory()->create());

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('form.email', 'anyone@example.com')
        ->set('form.password', 'NewPass123')
        ->call('resetPassword')
        ->assertHasErrors(['form.password']);
});

test('reset password password must be at least 8 characters', function () {
    $token = Password::createToken(User::factory()->create());

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('form.email', 'anyone@example.com')
        ->set('form.password', 'short')
        ->set('form.password_confirmation', 'short')
        ->call('resetPassword')
        ->assertHasErrors(['form.password']);
});

// ─── Successful Reset ───

test('reset password succeeds with valid token and matching passwords', function () {
    $user = User::factory()->create([
        'email' => 'bob@example.com',
        'password' => Hash::make('OldPass123'),
    ]);
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('form.email', 'bob@example.com')
        ->set('form.password', 'NewStrongPass1')
        ->set('form.password_confirmation', 'NewStrongPass1')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('NewStrongPass1', $user->fresh()->password))->toBeTrue();
});

// ─── Failed Reset ───

test('reset password fails with invalid token', function () {
    $user = User::factory()->create([
        'email' => 'carol@example.com',
        'password' => Hash::make('OldPass123'),
    ]);

    Livewire::test(ResetPassword::class, ['token' => 'invalid-token'])
        ->set('form.email', 'carol@example.com')
        ->set('form.password', 'NewStrongPass1')
        ->set('form.password_confirmation', 'NewStrongPass1')
        ->call('resetPassword')
        ->assertHasErrors(['form.email']);

    expect(Hash::check('OldPass123', $user->fresh()->password))->toBeTrue();
});

test('reset password fails with wrong email', function () {
    $user = User::factory()->create([
        'email' => 'dave@example.com',
        'password' => Hash::make('OldPass123'),
    ]);
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('form.email', 'wrong@example.com')
        ->set('form.password', 'NewStrongPass1')
        ->set('form.password_confirmation', 'NewStrongPass1')
        ->call('resetPassword')
        ->assertHasErrors(['form.email']);

    expect(Hash::check('OldPass123', $user->fresh()->password))->toBeTrue();
});

test('reset password fails when passwords do not match', function () {
    $user = User::factory()->create([
        'email' => 'frank@example.com',
        'password' => Hash::make('OldPass123'),
    ]);
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('form.email', 'frank@example.com')
        ->set('form.password', 'NewStrongPass1')
        ->set('form.password_confirmation', 'DifferentPass1')
        ->call('resetPassword')
        ->assertHasErrors(['form.password']);

    expect(Hash::check('OldPass123', $user->fresh()->password))->toBeTrue();
});

// ─── Rate Limiting ───

test('reset password throttles after 5 attempts', function () {
    $user = User::factory()->create([
        'email' => 'grace@example.com',
        'password' => Hash::make('OldPass123'),
    ]);
    // Use an invalid token so all attempts fail at the action level
    $invalidToken = 'invalid-token-value';

    $component = Livewire::test(ResetPassword::class, ['token' => $invalidToken]);

    // 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $component
            ->set('form.email', 'grace@example.com')
            ->set('form.password', 'NewStrongPass1')
            ->set('form.password_confirmation', 'NewStrongPass1')
            ->call('resetPassword');
    }

    // 6th attempt should be throttled
    $component
        ->set('form.email', 'grace@example.com')
        ->set('form.password', 'NewStrongPass1')
        ->set('form.password_confirmation', 'NewStrongPass1')
        ->call('resetPassword')
        ->assertHasErrors(['form.password']);
});
