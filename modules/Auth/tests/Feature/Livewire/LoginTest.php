<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Authentication\Livewire;

use Livewire\Livewire;
use Modules\Auth\Livewire\Login;
use Modules\User\Models\User;

describe('Login Component', function () {
    test('it renders the login form correctly [SYRS-NF-501]', function () {
        Livewire::test(Login::class)
            ->assertSee(__('auth::ui.login.title'))
            ->assertSee(__('auth::ui.login.form.identifier'));
    });

    test('it allows a user to login with valid credentials', function () {
        // Bypass Turnstile for testing
        config(['services.cloudflare.turnstile.secret_key' => null]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->set('identifier', 'test@example.com')
            ->set('password', 'password')
            ->set('captcha_token', 'dummy-token')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    });
});
