<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\AbsenceRequest\Entities\AbsenceRequestState;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;
use Illuminate\Database\Eloquent\Model;

final class AbsenceRequestStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('1KSWL: absence request state', function (): void {
    test('1KSWL-FR-DAILY-012: fromModel bridges a pending request without persisting', function (): void {
        $model = new AbsenceRequestStateModelDouble(['status' => AbsenceRequestStatus::PENDING]);

        $state = AbsenceRequestState::fromModel($model);

        expect($state->isPending())->toBeTrue();
        expect($state->isProcessed())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('1KSWL-FR-DAILY-013: fromModel bridges a processed request', function (): void {
        $model = new AbsenceRequestStateModelDouble(['status' => AbsenceRequestStatus::APPROVED]);

        $state = AbsenceRequestState::fromModel($model);

        expect($state->isPending())->toBeFalse();
        expect($state->isProcessed())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('1KSWL-FR-DAILY-012: fromArray accepts a null status and rejects a missing key', function (): void {
        $state = AbsenceRequestState::fromArray(['status' => null]);

        expect($state->isPending())->toBeFalse();
        expect($state->isProcessed())->toBeFalse();

        expect(fn (): AbsenceRequestState => AbsenceRequestState::fromArray([]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('1KSWL-FR-DAILY-013: isProcessed is true for approved and rejected alike', function (): void {
        expect(AbsenceRequestState::fromArray(['status' => AbsenceRequestStatus::REJECTED])->isProcessed())->toBeTrue();
        expect(AbsenceRequestState::fromArray(['status' => AbsenceRequestStatus::REJECTED])->isPending())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-013: toArray, equals, and with round-trip by value', function (): void {
        $state = AbsenceRequestState::fromArray(['status' => AbsenceRequestStatus::PENDING]);

        expect($state->toArray())->toBe(['status' => AbsenceRequestStatus::PENDING]);
        expect($state->equals(AbsenceRequestState::fromArray(['status' => AbsenceRequestStatus::PENDING])))->toBeTrue();

        $approved = $state->with('status', AbsenceRequestStatus::APPROVED);

        expect($approved->isProcessed())->toBeTrue();
        expect($state->isPending())->toBeTrue();
    });
});
