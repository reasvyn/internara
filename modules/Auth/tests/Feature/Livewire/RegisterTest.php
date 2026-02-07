<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Auth\Livewire\Register;

test('register component renders correctly', function () {
    Livewire::test(Register::class)
        ->assertSee(__('auth::ui.register.title'))
        ->assertSee(__('auth::ui.register.form.name'));
});

test('user can register new account', function () {
    Livewire::test(Register::class)
        ->set('name', 'New User')
        ->set('email', 'new@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('captcha_token', 'valid-token') // Assuming mock turnstile
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
});
