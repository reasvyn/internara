<?php

declare(strict_types=1);

use App\User\Enums\AccountStatus;
use App\User\Enums\Role as RoleEnum;
use App\User\Login\Livewire\Login as LoginComponent;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

// ─── Guest Access ───

test('guest can visit login page', function () {
    $this->get(route('login'))
        ->assertStatus(200)
        ->assertSee(__('auth.login.title'));
});

test('authenticated user is redirected from login page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect('/dashboard');
});

// ─── Successful Login ───

test('user can login with email via Livewire component', function () {
    $user = User::factory()->create([
        'email' => 'login-test@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'login-test@example.com')
        ->set('form.password', 'SecretPass1')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/dashboard');

    expect(auth()->check())->toBeTrue();
});

test('user can login with username via Livewire component', function () {
    $user = User::factory()->create([
        'email' => 'username-login@example.com',
        'username' => 'testuser_name',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'testuser_name')
        ->set('form.password', 'SecretPass1')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/dashboard');

    expect(auth()->check())->toBeTrue();
});

test('user is redirected to intended URL after login', function () {
    $user = User::factory()->create([
        'email' => 'intended@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    session()->put('url.intended', '/profile');

    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'intended@example.com')
        ->set('form.password', 'SecretPass1')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/profile');
});

test('login with remember me sets remember token', function () {
    $user = User::factory()->create([
        'email' => 'remember@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'remember@example.com')
        ->set('form.password', 'SecretPass1')
        ->set('form.remember', true)
        ->call('login')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue();
});

// ─── Validation Errors ───

test('login requires identifier', function () {
    Livewire::test(LoginComponent::class)
        ->set('form.password', 'SomePass1')
        ->call('login')
        ->assertHasErrors(['form.identifier']);
});

test('login requires password', function () {
    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'someuser@example.com')
        ->call('login')
        ->assertHasErrors(['form.password']);
});

// ─── Failed Login ───

test('login shows error for wrong password', function () {
    $user = User::factory()->create([
        'email' => 'wrongpw@example.com',
        'password' => Hash::make('CorrectPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'wrongpw@example.com')
        ->set('form.password', 'WrongPass1')
        ->call('login')
        ->assertHasErrors(['form.identifier'])
        ->assertSee(__('auth.failed'));

    expect(auth()->check())->toBeFalse();
});

test('login shows error for non-existent user', function () {
    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'nobody@example.com')
        ->set('form.password', 'SomePass1')
        ->call('login')
        ->assertHasErrors(['form.identifier']);

    expect(auth()->check())->toBeFalse();
});

test('login shows blocked error for locked account', function () {
    $user = User::factory()->locked()->create([
        'email' => 'locked-user@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'locked-user@example.com')
        ->set('form.password', 'SecretPass1')
        ->call('login')
        ->assertHasErrors(['form.identifier']);

    expect(auth()->check())->toBeFalse();
});

test('login shows blocked error when setup is required', function () {
    $user = User::factory()->requiresSetup()->create([
        'email' => 'setup-needed@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'setup-needed@example.com')
        ->set('form.password', 'SecretPass1')
        ->call('login')
        ->assertHasErrors(['form.identifier']);

    expect(auth()->check())->toBeFalse();
});

// ─── Rate Limiting ───

test('login shows throttle error after too many attempts', function () {
    $user = User::factory()->create([
        'email' => 'ratelimit@example.com',
        'password' => Hash::make('CorrectPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $component = Livewire::test(LoginComponent::class);

    // Exhaust the component-level rate limiter (5 attempts)
    for ($i = 0; $i < 5; $i++) {
        $component
            ->set('form.identifier', 'ratelimit@example.com')
            ->set('form.password', 'wrong_pass')
            ->call('login');
    }

    // 6th attempt should show throttle
    $component
        ->set('form.identifier', 'ratelimit@example.com')
        ->set('form.password', 'wrong_pass')
        ->call('login')
        ->assertHasErrors(['form.identifier']);

    expect(auth()->check())->toBeFalse();
});

// ─── Intended URL ───

test('login defaults to /dashboard when no intended URL', function () {
    $user = User::factory()->create([
        'email' => 'default-redirect@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    Livewire::test(LoginComponent::class)
        ->set('form.identifier', 'default-redirect@example.com')
        ->set('form.password', 'SecretPass1')
        ->call('login')
        ->assertRedirect('/dashboard');
});
