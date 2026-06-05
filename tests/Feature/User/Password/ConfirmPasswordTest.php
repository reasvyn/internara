<?php

declare(strict_types=1);

use App\User\Enums\Role as RoleEnum;
use App\User\Models\User;
use App\User\Password\Livewire\ConfirmPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
    Cache::flush();
});

afterEach(function () {
    RateLimiter::clear('confirm-password|127.0.0.1');
});

// ─── Authentication Required ───

test('guest is redirected from confirm password page', function () {
    $this->get(route('password.confirm'))
        ->assertRedirect(route('login'));
});

// ─── Form Validation ───

test('confirm password requires password', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ConfirmPassword::class)
        ->call('confirm')
        ->assertHasErrors(['form.password']);
});

// ─── Successful Confirmation ───

test('confirm password succeeds with correct password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CorrectPass1'),
    ]);

    Livewire::actingAs($user)
        ->test(ConfirmPassword::class)
        ->set('form.password', 'CorrectPass1')
        ->call('confirm')
        ->assertHasNoErrors()
        ->assertRedirect('/dashboard');

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

test('confirm password redirects to intended URL', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CorrectPass1'),
    ]);

    session()->put('url.intended', '/profile');

    Livewire::actingAs($user)
        ->test(ConfirmPassword::class)
        ->set('form.password', 'CorrectPass1')
        ->call('confirm')
        ->assertRedirect('/profile');
});

// ─── Failed Confirmation ───

test('confirm password fails with wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CorrectPass1'),
    ]);

    Livewire::actingAs($user)
        ->test(ConfirmPassword::class)
        ->set('form.password', 'WrongPass1')
        ->call('confirm')
        ->assertHasErrors(['form.password']);

    expect(session('auth.password_confirmed_at'))->toBeNull();
});

// ─── Rate Limiting ───

test('confirm password throttles after 5 attempts', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CorrectPass1'),
    ]);

    $component = Livewire::actingAs($user)
        ->test(ConfirmPassword::class);

    for ($i = 0; $i < 5; $i++) {
        $component
            ->set('form.password', 'WrongPass1')
            ->call('confirm');
    }

    $component
        ->set('form.password', 'WrongPass1')
        ->call('confirm')
        ->assertHasErrors(['form.password']);
});
