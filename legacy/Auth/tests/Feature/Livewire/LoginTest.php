<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Authentication\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Auth\Livewire\Login;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;

describe('Login Component', function () {
    beforeEach(function () {
        config(['services.cloudflare.turnstile.secret_key' => null]);
    });

    test('it renders the login form correctly [SYRS-NF-501]', function () {
        Livewire::test(Login::class)
            ->assertSee(__('auth::ui.login.title'))
            ->assertSee(__('auth::ui.login.form.identifier'));
    });

    test('it allows a user to login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    });

    test('it rejects invalid credentials with appropriate error message', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['identifier'])
            ->assertSee(__('auth::exceptions.invalid_credentials'));

        $this->assertGuest();
    });

    test('it rejects blocked account (suspended)', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $user->setStatus(Status::SUSPENDED->value, 'Test suspension');

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['identifier'])
            ->assertSee(__('auth::exceptions.account_blocked'));

        $this->assertGuest();
    });

    test('it rejects blocked account (inactive)', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $user->setStatus(Status::INACTIVE->value, 'Test inactivity');

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['identifier'])
            ->assertSee(__('auth::exceptions.account_blocked'));

        $this->assertGuest();
    });

    test('it blocks login after rate limit exceeded', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $throttleKey = Str::transliterate(Str::lower('test@example.com').'|'.request()->ip());
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($throttleKey, 60);
        }

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['identifier']);

        $this->assertGuest();
    });

    test('it requires identifier field', function () {
        Livewire::test(Login::class)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['identifier']);
    });

    test('it requires password field', function () {
        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->call('login')
            ->assertHasErrors(['password']);
    });
});
