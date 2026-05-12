<?php

declare(strict_types=1);

use App\Actions\Auth\GenerateRecoverySlipAction;
use App\Actions\Auth\RedeemRecoverySlipAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('redeems a valid recovery slip and updates password', function () {
        $user = UserFactory::new()->create(['username' => 'testuser']);
        $slip = app(GenerateRecoverySlipAction::class)->execute($user);

        $result = app(RedeemRecoverySlipAction::class)->execute(
            username: 'testuser',
            code: $slip['plaintext'][0],
            newPassword: 'new-secure-password',
        );

        expect($result->id)->toBe($user->id);
    });

    it('throws RuntimeException for invalid username', function () {
        expect(fn () => app(RedeemRecoverySlipAction::class)->execute(
            username: 'nonexistent',
            code: 'SOME-CODE',
            newPassword: 'new-password',
        ))->toThrow(RuntimeException::class);
    });

    it('throws RuntimeException for invalid code', function () {
        UserFactory::new()->create(['username' => 'testuser']);

        expect(fn () => app(RedeemRecoverySlipAction::class)->execute(
            username: 'testuser',
            code: 'INVALID-CODE',
            newPassword: 'new-password',
        ))->toThrow(RuntimeException::class);
    });
});
