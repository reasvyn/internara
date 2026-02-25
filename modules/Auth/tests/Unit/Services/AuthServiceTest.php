<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Services\AuthService;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

describe('Auth Service', function () {
    beforeEach(function () {
        $this->userService = mock(UserService::class);
        $this->service = new AuthService($this->userService);
    });

    test('it can authenticate a user with email', function () {
        Auth::shouldReceive('attempt')
            ->once()
            ->with(['email' => 'test@example.com', 'password' => 'password'], false)
            ->andReturn(true);

        Auth::shouldReceive('user')->andReturn(new User);

        $user = $this->service->login([
            'identifier' => 'test@example.com',
            'password' => 'password',
        ]);

        expect($user)->toBeInstanceOf(User::class);
    });

    test('it can authenticate a user with username', function () {
        Auth::shouldReceive('attempt')
            ->once()
            ->with(['username' => 'testuser', 'password' => 'password'], false)
            ->andReturn(true);

        Auth::shouldReceive('user')->andReturn(new User);

        $user = $this->service->login(['identifier' => 'testuser', 'password' => 'password']);

        expect($user)->toBeInstanceOf(User::class);
    });

    test('it prevents role escalation during registration [SYRS-NF-502]', function () {
        $data = [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
            'role' => Role::SUPER_ADMIN->value, // Unauthorized role injection
        ];

        $this->userService
            ->shouldReceive('create')
            ->once()
            ->with(
                \Mockery::on(function ($arg) {
                    // Ensure 'role' or 'roles' from input is removed/ignored
                    return ! isset($arg['role']) && $arg['roles'] === Role::STUDENT->value;
                }),
            )
            ->andReturn(new User);

        $this->service->register($data, Role::STUDENT->value);
    });

    test('it can logout a user', function () {
        Auth::shouldReceive('logout')->once();

        $this->service->logout();
        expect(true)->toBeTrue();
    });
});
