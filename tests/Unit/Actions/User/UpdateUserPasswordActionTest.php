<?php

declare(strict_types=1);

use App\Actions\User\UpdateUserPasswordAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates user password', function () {
        $user = UserFactory::new()->create();

        app(UpdateUserPasswordAction::class)->execute($user, 'new-secure-password');

        expect(true)->toBeTrue();
    });

    it('throws validation error for short password', function () {
        $user = UserFactory::new()->create();

        expect(fn () => app(UpdateUserPasswordAction::class)->execute($user, 'short'))
            ->toThrow(ValidationException::class);
    });
});
