<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccessTokens\Models\AccessToken;
use App\Modules\Auth\Domain\Account\Actions\ActivateAccountAction;
use App\Modules\Auth\Domain\Account\Data\ActivateAccountData;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: ActivateAccountAction', function (): void {
    test('YB7RG-FR-AUTH-029: valid code activates the account, hashes the password, and revokes the token', function (): void {
        $user = User::factory()->create();
        $generated = AccessToken::generateFor($user, 'activation');

        $result = app(ActivateAccountAction::class)->execute(new ActivateAccountData(
            userId: $user->id,
            code: $generated['plain_text'],
            password: 'brand-new-secret',
        ));

        expect($result->id)->toBe($user->id)
            ->and(Hash::check('brand-new-secret', $user->fresh()->password))->toBeTrue()
            ->and(AccessToken::where('user_id', $user->id)
                ->where('token_type', 'activation')
                ->whereNull('revoked_at')
                ->exists())->toBeFalse();
    });

    test('YB7RG-FR-AUTH-033: wrong code throws RejectedException and keeps the old password', function (): void {
        $user = User::factory()->withPassword('original-secret')->create();
        AccessToken::generateFor($user, 'activation');

        expect(fn () => app(ActivateAccountAction::class)->execute(new ActivateAccountData(
            userId: $user->id,
            code: 'not-the-real-code',
            password: 'brand-new-secret',
        )))->toThrow(RejectedException::class);

        expect(Hash::check('original-secret', $user->fresh()->password))->toBeTrue();
    });

    test('YB7RG-FR-AUTH-033: unknown user id throws RejectedException', function (): void {
        expect(fn () => app(ActivateAccountAction::class)->execute(new ActivateAccountData(
            userId: (string) Str::uuid(),
            code: 'any-code',
            password: 'brand-new-secret',
        )))->toThrow(RejectedException::class);
    });
});
