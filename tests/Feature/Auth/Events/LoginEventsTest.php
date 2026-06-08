<?php

declare(strict_types=1);

use App\Auth\Login\Actions\LoginAction;
use App\Auth\Login\Events\LoginFailed;
use App\Auth\Login\Events\LoginSucceeded;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function () {
    \Spatie\Permission\Models\Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
});

test('login succeeded event is dispatched on successful login', function () {
    $user = \App\User\Models\User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('superadmin');

    Event::fake([LoginSucceeded::class]);

    app(LoginAction::class)->execute('test@example.com', 'password');

    Event::assertDispatched(LoginSucceeded::class, function (LoginSucceeded $event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('login failed event is dispatched on invalid credentials', function () {
    Event::fake([LoginFailed::class]);

    try {
        app(LoginAction::class)->execute('nonexistent@test.com', 'wrong');
    } catch (\RuntimeException) {
        // expected
    }

    Event::assertDispatched(LoginFailed::class);
});