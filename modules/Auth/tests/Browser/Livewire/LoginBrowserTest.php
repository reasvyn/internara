<?php

declare(strict_types=1);

use Modules\User\Models\User;

test('user can login via browser', function () {
    $user = User::factory()->create([
        'email' => 'browser@example.com',
        'password' => 'password',
    ]);

    $this->browse(function ($browser) use ($user) {
        $browser
            ->visitRoute('login')
            ->type('identifier', $user->email)
            ->type('password', 'password')
            ->press(__('auth::ui.login.form.submit'))
            ->assertPathIsNot('/login');
    });
});
