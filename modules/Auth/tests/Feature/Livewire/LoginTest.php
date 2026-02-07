<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Auth\Livewire\Login;
use Modules\User\Models\User;

test('login component renders correctly', function () {
    Livewire::test(Login::class)
        ->assertSee(__('auth::ui.login.title'))
        ->assertSee(__('auth::ui.login.form.identifier'));
});

test('user can login with valid credentials', function () {
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
