<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Account\Entities\AccountActivation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class AccountActivationModelDouble extends Model
{
    protected $guarded = [];
}

describe('YB7RG: account activation', function (): void {
    test('YB7RG-FR-AUTH-034: fromModel treats a missing token as already activated', function (): void {
        $model = new AccountActivationModelDouble;

        $activation = AccountActivation::fromModel($model);

        expect($activation->requiresActivation())->toBeFalse();
        expect($activation->isTokenValid())->toBeTrue();
        expect($activation->isTokenExpired())->toBeFalse();
        expect($activation->tokenExpiresAt())->toBeNull();
        expect($activation->attempts())->toBe(0);
        expect($activation->hasExceededMaxAttempts())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-034: fromModel bridges a live token from the loaded relation', function (): void {
        $expiresAt = Carbon::now()->addDay();
        $token = new AccountActivationModelDouble(['expires_at' => $expiresAt, 'attempts' => 2]);
        $model = new AccountActivationModelDouble;
        $model->setRelation('activationToken', $token);

        $activation = AccountActivation::fromModel($model);

        expect($activation->requiresActivation())->toBeTrue();
        expect($activation->isTokenValid())->toBeTrue();
        expect($activation->isTokenExpired())->toBeFalse();
        expect($activation->tokenExpiresAt()?->equalTo($expiresAt))->toBeTrue();
        expect($activation->attempts())->toBe(2);
        expect($activation->hasExceededMaxAttempts())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-033: fromModel flags a past token as expired', function (): void {
        $token = new AccountActivationModelDouble(['expires_at' => Carbon::now()->subDay(), 'attempts' => 5]);
        $model = new AccountActivationModelDouble;
        $model->setRelation('activationToken', $token);

        $activation = AccountActivation::fromModel($model);

        expect($activation->requiresActivation())->toBeTrue();
        expect($activation->isTokenValid())->toBeFalse();
        expect($activation->isTokenExpired())->toBeTrue();
        expect($activation->hasExceededMaxAttempts())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-034: fromArray hydrates the snapshot and rejects missing params', function (): void {
        $activation = AccountActivation::fromArray([
            'isActivated' => false,
            'tokenExpiresAt' => null,
            'tokenIsValid' => true,
            'attempts' => 0,
        ]);

        expect($activation->requiresActivation())->toBeTrue();
        expect($activation->attempts())->toBe(0);

        expect(fn (): AccountActivation => AccountActivation::fromArray([
            'isActivated' => false,
            'tokenExpiresAt' => null,
            'tokenIsValid' => true,
        ]))->toThrow(InvalidArgumentException::class, 'attempts');
    });

    test('YB7RG-FR-AUTH-034: hasExceededMaxAttempts honours the default and custom ceilings', function (): void {
        $limited = AccountActivation::fromArray(['isActivated' => false, 'tokenExpiresAt' => null, 'tokenIsValid' => true, 'attempts' => 5]);
        $fresh = AccountActivation::fromArray(['isActivated' => false, 'tokenExpiresAt' => null, 'tokenIsValid' => true, 'attempts' => 4]);

        expect($limited->hasExceededMaxAttempts())->toBeTrue();
        expect($fresh->hasExceededMaxAttempts())->toBeFalse();
        expect($fresh->hasExceededMaxAttempts(4))->toBeTrue();
    });
});
