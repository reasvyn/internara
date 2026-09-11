<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Entities\SubmissionState;
use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;

final class SubmissionStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('T657Z: submission state', function (): void {
    test('T657Z-FR-SUBM-009: fromModel bridges the status without persisting', function (): void {
        $model = new SubmissionStateModelDouble(['status' => SubmissionStatus::SUBMITTED]);

        $state = SubmissionState::fromModel($model);

        expect($state->canBeEdited())->toBeFalse();
        expect($state->isVerified())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('T657Z-FR-SUBM-009: fromArray hydrates the status and rejects a missing one', function (): void {
        $state = SubmissionState::fromArray(['status' => SubmissionStatus::DRAFT]);

        expect($state->canBeEdited())->toBeTrue();

        expect(fn (): SubmissionState => SubmissionState::fromArray([]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('T657Z-FR-SUBM-010: canBeEdited is true only for draft and revision-required', function (): void {
        expect(SubmissionState::fromArray(['status' => SubmissionStatus::DRAFT])->canBeEdited())->toBeTrue();
        expect(SubmissionState::fromArray(['status' => SubmissionStatus::REVISION_REQUIRED])->canBeEdited())->toBeTrue();
        expect(SubmissionState::fromArray(['status' => SubmissionStatus::SUBMITTED])->canBeEdited())->toBeFalse();
        expect(SubmissionState::fromArray(['status' => SubmissionStatus::VERIFIED])->canBeEdited())->toBeFalse();
        expect(SubmissionState::fromArray(['status' => SubmissionStatus::GRADED])->canBeEdited())->toBeFalse();
    });

    test('T657Z-FR-SUBM-010: isVerified is true only for verified', function (): void {
        expect(SubmissionState::fromArray(['status' => SubmissionStatus::VERIFIED])->isVerified())->toBeTrue();
        expect(SubmissionState::fromArray(['status' => SubmissionStatus::GRADED])->isVerified())->toBeFalse();
        expect(SubmissionState::fromArray(['status' => SubmissionStatus::DRAFT])->isVerified())->toBeFalse();
    });

    test('T657Z-FR-SUBM-010: toArray, equals, and with round-trip by value', function (): void {
        $state = SubmissionState::fromArray(['status' => SubmissionStatus::DRAFT]);

        expect($state->toArray())->toBe(['status' => SubmissionStatus::DRAFT]);
        expect($state->equals(SubmissionState::fromArray(['status' => SubmissionStatus::DRAFT])))->toBeTrue();
        expect($state->equals(SubmissionState::fromArray(['status' => SubmissionStatus::VERIFIED])))->toBeFalse();

        $verified = $state->with('status', SubmissionStatus::VERIFIED);

        expect($verified->isVerified())->toBeTrue();
        expect($state->isVerified())->toBeFalse();
    });
});
