<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => Hash::make('password123'),
    ]);
    $this->user->assignRole('student');
    $this->user->setStatus('active');
});

it('renders the login page', function () {
    Livewire::test(Login::class)
        ->assertSuccessful();
});

it('logs in with email', function () {
    Livewire::test(Login::class)
        ->set('identifier', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->id)->toBe($this->user->id);
});

it('logs in with username', function () {
    Livewire::test(Login::class)
        ->set('identifier', 'testuser')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->id)->toBe($this->user->id);
});

it('fails with wrong password', function () {
    Livewire::test(Login::class)
        ->set('identifier', 'test@example.com')
        ->set('password', 'wrongpassword')
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth()->check())->toBeFalse();
});

it('fails with non-existent user', function () {
    Livewire::test(Login::class)
        ->set('identifier', 'nonexistent@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth()->check())->toBeFalse();
});

it('fails with blank fields', function () {
    Livewire::test(Login::class)
        ->call('login')
        ->assertHasErrors([
            'identifier' => 'required',
            'password' => 'required',
        ]);
});

it('blocks suspended accounts', function () {
    $this->user->setStatus('suspended');

    Livewire::test(Login::class)
        ->set('identifier', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth()->check())->toBeFalse();
});

it('blocks archived accounts', function () {
    $this->user->setStatus('archived');

    Livewire::test(Login::class)
        ->set('identifier', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth()->check())->toBeFalse();
});

it('blocks inactive accounts', function () {
    $this->user->setStatus('inactive');

    Livewire::test(Login::class)
        ->set('identifier', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth()->check())->toBeFalse();
});

it('respects remember me flag', function () {
    Livewire::test(Login::class)
        ->set('identifier', 'test@example.com')
        ->set('password', 'password123')
        ->set('remember', true)
        ->call('login')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue();
});

it('redirects to dashboard after successful login', function () {
    Livewire::test(Login::class)
        ->set('identifier', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect('/dashboard');
});

it('rate limits after multiple failed attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'wrongpassword')
            ->call('login');
    }

    Livewire::test(Login::class)
        ->set('identifier', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors('identifier');

    expect(auth()->check())->toBeFalse();
});
