<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Services\AuthService;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

test('it can authenticate a user with email', function () {
    $userService = mock(UserService::class);
    $service = new AuthService($userService);

    Auth::shouldReceive('attempt')
        ->once()
        ->with(['email' => 'test@example.com', 'password' => 'password'], false)
        ->andReturn(true);

    Auth::shouldReceive('user')->andReturn(new User);

    $user = $service->login(['email' => 'test@example.com', 'password' => 'password']);

    expect($user)->toBeInstanceOf(User::class);
});

test('it can authenticate a user with username', function () {
    $userService = mock(UserService::class);
    $service = new AuthService($userService);

    Auth::shouldReceive('attempt')
        ->once()
        ->with(['username' => 'testuser', 'password' => 'password'], false)
        ->andReturn(true);

    Auth::shouldReceive('user')->andReturn(new User);

    $user = $service->login(['identifier' => 'testuser', 'password' => 'password']);

    expect($user)->toBeInstanceOf(User::class);
});

test('it can logout a user', function () {
    $userService = mock(UserService::class);
    $service = new AuthService($userService);

    Auth::shouldReceive('logout')->once();

    $service->logout();
    expect(true)->toBeTrue();
});
