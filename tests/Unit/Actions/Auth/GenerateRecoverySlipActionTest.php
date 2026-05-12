<?php

declare(strict_types=1);

use App\Actions\Auth\GenerateRecoverySlipAction;
use App\Models\AccountRecoveryCode;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('generates a recovery code for user', function () {
        $user = UserFactory::new()->create();

        $result = app(GenerateRecoverySlipAction::class)->execute($user);

        expect($result)->toHaveKeys(['code', 'plaintext'])
            ->and($result['code'])->toBeInstanceOf(AccountRecoveryCode::class)
            ->and($result['code']->user_id)->toBe($user->id)
            ->and($result['plaintext'])->toBeArray()
            ->and($result['plaintext'])->toHaveCount(10)
            ->and($result['plaintext'][0])->toBeString();
    });
});
