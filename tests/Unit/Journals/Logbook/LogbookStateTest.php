<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\Logbook\Entities\LogbookState;
use App\Modules\Journals\Domain\Logbook\Enums\LogbookStatus;
use Illuminate\Database\Eloquent\Model;

final class LogbookStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('1KSWL: logbook state', function (): void {
    test('1KSWL-FR-DAILY-003: fromModel bridges the status without persisting', function (): void {
        $model = new LogbookStateModelDouble(['status' => LogbookStatus::SUBMITTED]);

        $state = LogbookState::fromModel($model);

        expect($state->isVerified())->toBeFalse();
        expect($state->canBeEdited())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('1KSWL-FR-DAILY-005: canBeEdited is true only for draft and revision-required', function (): void {
        expect(LogbookState::fromArray(['status' => LogbookStatus::DRAFT])->canBeEdited())->toBeTrue();
        expect(LogbookState::fromArray(['status' => LogbookStatus::REVISION_REQUIRED])->canBeEdited())->toBeTrue();
        expect(LogbookState::fromArray(['status' => LogbookStatus::SUBMITTED])->canBeEdited())->toBeFalse();
        expect(LogbookState::fromArray(['status' => LogbookStatus::VERIFIED])->canBeEdited())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-006: isVerified is true only for verified', function (): void {
        expect(LogbookState::fromArray(['status' => LogbookStatus::VERIFIED])->isVerified())->toBeTrue();
        expect(LogbookState::fromArray(['status' => LogbookStatus::SUBMITTED])->isVerified())->toBeFalse();
        expect(LogbookState::fromArray(['status' => LogbookStatus::DRAFT])->isVerified())->toBeFalse();
    });

    test('1KSWL-FR-DAILY-003: fromArray rejects a missing status', function (): void {
        expect(fn (): LogbookState => LogbookState::fromArray([]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('1KSWL-FR-DAILY-003: toArray, equals, and with round-trip by value', function (): void {
        $state = LogbookState::fromArray(['status' => LogbookStatus::DRAFT]);

        expect($state->toArray())->toBe(['status' => LogbookStatus::DRAFT]);
        expect($state->equals(LogbookState::fromArray(['status' => LogbookStatus::DRAFT])))->toBeTrue();

        $verified = $state->with('status', LogbookStatus::VERIFIED);

        expect($verified->isVerified())->toBeTrue();
        expect($state->isVerified())->toBeFalse();
    });
});
