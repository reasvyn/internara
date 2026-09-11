<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccessTokens\Entities\AccessTokenState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class AccessTokenStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('YB7RG: access token state', function (): void {
    test('YB7RG-FR-AUTH-039: fromModel bridges the token row without persisting', function (): void {
        $model = new AccessTokenStateModelDouble([
            'expires_at' => Carbon::now()->addDay(),
            'revoked_at' => null,
            'attempts' => 1,
        ]);

        $state = AccessTokenState::fromModel($model);

        expect($state->isExpired())->toBeFalse();
        expect($state->isRevoked())->toBeFalse();
        expect($state->isValid())->toBeTrue();
        expect($state->hasExceededMaxAttempts())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-039: fromModel defaults missing attempts to zero', function (): void {
        $model = new AccessTokenStateModelDouble(['expires_at' => null, 'revoked_at' => null]);

        $state = AccessTokenState::fromModel($model);

        expect($state->hasExceededMaxAttempts())->toBeFalse();
        expect($state->isValid())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-039: fromArray hydrates the triplet and rejects missing params', function (): void {
        $state = AccessTokenState::fromArray(['expiresAt' => null, 'revokedAt' => null, 'attempts' => 0]);

        expect($state->isValid())->toBeTrue();

        expect(fn (): AccessTokenState => AccessTokenState::fromArray(['expiresAt' => null, 'revokedAt' => null]))
            ->toThrow(InvalidArgumentException::class, 'attempts');
    });

    test('YB7RG-FR-AUTH-036: isExpired is true only for a past expiry', function (): void {
        $expired = AccessTokenState::fromArray(['expiresAt' => Carbon::now()->subDay(), 'revokedAt' => null, 'attempts' => 0]);
        $fresh = AccessTokenState::fromArray(['expiresAt' => Carbon::now()->addDay(), 'revokedAt' => null, 'attempts' => 0]);
        $dateless = AccessTokenState::fromArray(['expiresAt' => null, 'revokedAt' => null, 'attempts' => 0]);

        expect($expired->isExpired())->toBeTrue();
        expect($fresh->isExpired())->toBeFalse();
        expect($dateless->isExpired())->toBeFalse();
    });

    test('YB7RG-FR-AUTH-036: isValid refuses revoked or expired tokens', function (): void {
        $revoked = AccessTokenState::fromArray(['expiresAt' => Carbon::now()->addDay(), 'revokedAt' => Carbon::now(), 'attempts' => 0]);
        $expired = AccessTokenState::fromArray(['expiresAt' => Carbon::now()->subDay(), 'revokedAt' => null, 'attempts' => 0]);
        $valid = AccessTokenState::fromArray(['expiresAt' => Carbon::now()->addDay(), 'revokedAt' => null, 'attempts' => 0]);

        expect($revoked->isRevoked())->toBeTrue();
        expect($revoked->isValid())->toBeFalse();
        expect($expired->isValid())->toBeFalse();
        expect($valid->isValid())->toBeTrue();
    });

    test('YB7RG-FR-AUTH-039: hasExceededMaxAttempts honours the default and custom ceilings', function (): void {
        $atLimit = AccessTokenState::fromArray(['expiresAt' => null, 'revokedAt' => null, 'attempts' => 5]);
        $below = AccessTokenState::fromArray(['expiresAt' => null, 'revokedAt' => null, 'attempts' => 4]);

        expect($atLimit->hasExceededMaxAttempts())->toBeTrue();
        expect($below->hasExceededMaxAttempts())->toBeFalse();
        expect($below->hasExceededMaxAttempts(4))->toBeTrue();
        expect($below->hasExceededMaxAttempts(5))->toBeFalse();
    });
});
