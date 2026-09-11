<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\SupervisionLog\Entities\SupervisionLogState;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class SupervisionLogStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('2EHSE: supervision log state', function (): void {
    test('2EHSE-FR-SUPV-004: fromModel stamps submission from creation on submitted logs', function (): void {
        $created = Carbon::parse('2026-04-01 09:00:00');
        $model = new SupervisionLogStateModelDouble(['status' => SupervisionLogStatus::SUBMITTED, 'reviewed_at' => null]);
        $model->setAttribute('created_at', $created);

        $state = SupervisionLogState::fromModel($model);

        expect($state->canBeEdited())->toBeFalse();
        expect($state->canBeSubmitted())->toBeFalse();
        expect($state->needsAcknowledgment())->toBeFalse();
        expect(Carbon::parse($state->toArray()['submittedAt'])->equalTo($created))->toBeTrue();
        expect($state->toArray()['reviewedAt'])->toBeNull();
        expect($model->exists)->toBeFalse();
    });

    test('2EHSE-FR-SUPV-005: fromModel bridges the review timestamp on reviewed logs', function (): void {
        $reviewedAt = Carbon::parse('2026-04-02 10:00:00');
        $model = new SupervisionLogStateModelDouble(['status' => SupervisionLogStatus::REVIEWED, 'reviewed_at' => $reviewedAt]);
        $model->setAttribute('created_at', Carbon::parse('2026-04-01 09:00:00'));

        $state = SupervisionLogState::fromModel($model);

        expect($state->needsAcknowledgment())->toBeTrue();
        expect(Carbon::parse($state->toArray()['reviewedAt'])->equalTo($reviewedAt))->toBeTrue();
        expect($state->toArray()['submittedAt'])->toBeNull();
        expect($model->exists)->toBeFalse();
    });

    test('2EHSE-FR-SUPV-006: canBeEdited and canBeSubmitted allow draft logs only', function (): void {
        $draft = SupervisionLogState::fromArray(['status' => SupervisionLogStatus::DRAFT, 'submittedAt' => null, 'reviewedAt' => null]);
        $submitted = SupervisionLogState::fromArray(['status' => SupervisionLogStatus::SUBMITTED, 'submittedAt' => Carbon::now(), 'reviewedAt' => null]);

        expect($draft->canBeEdited())->toBeTrue();
        expect($draft->canBeSubmitted())->toBeTrue();
        expect($submitted->canBeEdited())->toBeFalse();
        expect($submitted->canBeSubmitted())->toBeFalse();
    });

    test('2EHSE-FR-SUPV-004: needsAcknowledgment is true only for reviewed logs', function (): void {
        expect(SupervisionLogState::fromArray(['status' => SupervisionLogStatus::REVIEWED, 'submittedAt' => null, 'reviewedAt' => Carbon::now()])->needsAcknowledgment())->toBeTrue();
        expect(SupervisionLogState::fromArray(['status' => SupervisionLogStatus::SUBMITTED, 'submittedAt' => Carbon::now(), 'reviewedAt' => null])->needsAcknowledgment())->toBeFalse();
        expect(SupervisionLogState::fromArray(['status' => SupervisionLogStatus::ACKNOWLEDGED, 'submittedAt' => null, 'reviewedAt' => Carbon::now()])->needsAcknowledgment())->toBeFalse();
    });

    test('2EHSE-FR-SUPV-001: fromArray rejects a missing status', function (): void {
        expect(fn (): SupervisionLogState => SupervisionLogState::fromArray(['submittedAt' => null, 'reviewedAt' => null]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });
});
