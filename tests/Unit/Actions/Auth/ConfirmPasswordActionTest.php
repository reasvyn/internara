<?php

declare(strict_types=1);

use App\Actions\Auth\ConfirmPasswordAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('confirms password when correct', function () {
        $user = UserFactory::new()->create(['password' => bcrypt('correct-password')]);

        app(ConfirmPasswordAction::class)->execute($user, 'correct-password');

        expect(true)->toBeTrue();
    });

    it('throws RuntimeException when password is wrong', function () {
        $user = UserFactory::new()->create(['password' => bcrypt('correct-password')]);

        expect(fn () => app(ConfirmPasswordAction::class)->execute($user, 'wrong-password'))
            ->toThrow(RuntimeException::class);
    });
});
