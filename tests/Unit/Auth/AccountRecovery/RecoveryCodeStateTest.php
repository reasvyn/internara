<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccountRecovery\Entities\RecoveryCodeState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class RecoveryCodeStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('SHQ1J: recovery code state', function (): void {
    test('SHQ1J-FR-SLIP-008: fromModel bridges usage and expiry without persisting', function (): void {
        $model = new RecoveryCodeStateModelDouble([
            'last_used_at' => null,
            'expires_at' => Carbon::now()->addYear(),
        ]);

        $state = RecoveryCodeState::fromModel($model);

        expect($state->isValid(Carbon::now()))->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('SHQ1J-FR-SLIP-009: fromModel reports a consumed code as invalid', function (): void {
        $model = new RecoveryCodeStateModelDouble([
            'last_used_at' => Carbon::now()->subHour(),
            'expires_at' => Carbon::now()->addYear(),
        ]);

        $state = RecoveryCodeState::fromModel($model);

        expect($state->isValid(Carbon::now()))->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('SHQ1J-FR-SLIP-008: fromArray hydrates the pair and rejects missing params', function (): void {
        $state = RecoveryCodeState::fromArray(['usedAt' => null, 'expiresAt' => null]);

        expect($state->isValid(Carbon::now()))->toBeTrue();

        expect(fn (): RecoveryCodeState => RecoveryCodeState::fromArray(['usedAt' => null]))
            ->toThrow(InvalidArgumentException::class, 'expiresAt');
    });

    test('SHQ1J-FR-SLIP-008: isValid refuses used or past codes', function (): void {
        $now = Carbon::parse('2026-05-01 12:00:00');
        $fresh = RecoveryCodeState::fromArray(['usedAt' => null, 'expiresAt' => Carbon::parse('2026-06-01 00:00:00')]);
        $used = RecoveryCodeState::fromArray(['usedAt' => Carbon::parse('2026-04-01 00:00:00'), 'expiresAt' => Carbon::parse('2026-06-01 00:00:00')]);
        $stale = RecoveryCodeState::fromArray(['usedAt' => null, 'expiresAt' => Carbon::parse('2026-04-01 00:00:00')]);
        $dateless = RecoveryCodeState::fromArray(['usedAt' => null, 'expiresAt' => null]);

        expect($fresh->isValid($now))->toBeTrue();
        expect($used->isValid($now))->toBeFalse();
        expect($stale->isValid($now))->toBeFalse();
        expect($dateless->isValid($now))->toBeTrue();
    });
});
