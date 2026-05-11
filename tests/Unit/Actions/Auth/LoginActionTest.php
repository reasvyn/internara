<?php

declare(strict_types=1);

use App\Actions\Auth\LoginAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('throws RuntimeException when credentials are invalid', function () {
        UserFactory::new()->create(['email' => 'test@example.com', 'password' => bcrypt('correct')]);

        expect(fn () => app(LoginAction::class)->execute(
            identifier: 'test@example.com',
            password: 'wrong',
        ))->toThrow(RuntimeException::class);
    });

    it('throws RuntimeException when user is suspended', function () {
        $user = UserFactory::new()->create([
            'password' => bcrypt('secret'),
        ]);
        $user->setStatus('suspended');

        expect(fn () => app(LoginAction::class)->execute(
            identifier: $user->email,
            password: 'secret',
        ))->toThrow(RuntimeException::class);
    });

    it('throws RuntimeException when user is archived', function () {
        $user = UserFactory::new()->create([
            'password' => bcrypt('secret'),
        ]);
        $user->setStatus('archived');

        expect(fn () => app(LoginAction::class)->execute(
            identifier: $user->email,
            password: 'secret',
        ))->toThrow(RuntimeException::class);
    });

    it('throws RuntimeException when user is inactive', function () {
        $user = UserFactory::new()->create([
            'password' => bcrypt('secret'),
        ]);
        $user->setStatus('inactive');

        expect(fn () => app(LoginAction::class)->execute(
            identifier: $user->email,
            password: 'secret',
        ))->toThrow(RuntimeException::class);
    });

    it('resolves login by username when identifier has no @', function () {
        $user = UserFactory::new()->withPassword('secret')->create([
            'username' => 'johndoe',
        ]);

        $result = app(LoginAction::class)->execute(
            identifier: 'johndoe',
            password: 'secret',
        );

        expect($result->id)->toBe($user->id);
    });
});
