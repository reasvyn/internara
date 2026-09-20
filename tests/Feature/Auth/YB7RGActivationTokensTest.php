<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccessToken\Models\AccessToken;
use App\Modules\Auth\Domain\Account\Actions\ActivateAccountAction;
use App\Modules\Auth\Domain\Account\Data\ActivateAccountData;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: activation and access-token lifecycle', function (): void {
    test('YB7RG-FR-AUTH-030 + YB7RG-UC-AUTH-006 + YB7RG-DD-AUTH-003 + YB7RG-FR-AUTH-032: provisioned account activates once, code dies, transition is logged', function (): void {
        Log::spy();

        $user = User::factory()->create(['status' => 'provisioned']);
        $generated = AccessToken::generateFor($user, 'activation');

        $result = app(ActivateAccountAction::class)->execute(new ActivateAccountData(
            userId: $user->id,
            code: $generated['plain_text'],
            password: 'First-Secret-123',
        ));

        expect($result->id)->toBe($user->id)
            ->and(Hash::check('First-Secret-123', $user->fresh()->password))->toBeTrue()
            ->and(AccessToken::verify($user->fresh(), 'activation', $generated['plain_text']))->toBeFalse();

        $record = AccessToken::where('user_id', $user->id)->where('token_type', 'activation')->first();

        expect($record->revoked_at)->not->toBeNull();

        expect(fn () => app(ActivateAccountAction::class)->execute(new ActivateAccountData(
            userId: $user->id,
            code: $generated['plain_text'],
            password: 'Second-Secret-456',
        )))->toThrow(RejectedException::class);

        expect(Hash::check('First-Secret-123', $user->fresh()->password))->toBeTrue();

        Log::shouldHaveReceived('info')->with('account_activated', Mockery::on(
            fn ($context) => is_array($context) && ($context['event'] ?? null) === 'account_activated',
        ));
    });

    test('YB7RG-FR-AUTH-037: revocation stamps one type and leaves the others verifiable', function (): void {
        $user = User::factory()->create();
        $activation = AccessToken::generateFor($user, 'activation');
        $recovery = AccessToken::generateFor($user, 'recovery');

        expect(AccessToken::verify($user, 'activation', $activation['plain_text']))->toBeTrue()
            ->and(AccessToken::verify($user, 'recovery', $recovery['plain_text']))->toBeTrue();

        AccessToken::revokeFor($user, 'activation');

        $revoked = AccessToken::where('user_id', $user->id)->where('token_type', 'activation')->first();

        expect($revoked->revoked_at)->not->toBeNull()
            ->and(AccessToken::verify($user->fresh(), 'activation', $activation['plain_text']))->toBeFalse()
            ->and(AccessToken::verify($user->fresh(), 'recovery', $recovery['plain_text']))->toBeTrue();
    });

    test('YB7RG-FR-AUTH-038: bulk sweep revokes only the expired unrevoked tokens', function (): void {
        $expiredActivation = User::factory()->create();
        $expiredRecovery = User::factory()->create();
        $stillValid = User::factory()->create();
        $alreadyRevoked = User::factory()->create();

        AccessToken::generateFor($expiredActivation, 'activation');
        AccessToken::generateFor($expiredRecovery, 'recovery');
        $valid = AccessToken::generateFor($stillValid, 'activation');
        AccessToken::generateFor($alreadyRevoked, 'activation');

        AccessToken::where('user_id', $expiredActivation->id)->update(['expires_at' => now()->subDay()]);
        AccessToken::where('user_id', $expiredRecovery->id)->update(['expires_at' => now()->subDay()]);
        AccessToken::revokeFor($alreadyRevoked, 'activation');
        AccessToken::where('user_id', $alreadyRevoked->id)->update(['expires_at' => now()->subDay()]);

        $swept = AccessToken::revokeAllExpired();

        expect($swept)->toBe(2)
            ->and(AccessToken::where('user_id', $expiredActivation->id)->first()->revoked_at)->not->toBeNull()
            ->and(AccessToken::where('user_id', $expiredRecovery->id)->first()->revoked_at)->not->toBeNull()
            ->and(AccessToken::where('user_id', $stillValid->id)->first()->revoked_at)->toBeNull()
            ->and(AccessToken::verify($stillValid->fresh(), 'activation', $valid['plain_text']))->toBeTrue();
    });
});
