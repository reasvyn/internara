<?php

declare(strict_types=1);

use App\Modules\Partners\Domain\Partnership\Entities\PartnershipState;
use App\Modules\Partners\Domain\Partnership\Enums\PartnershipStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class PartnershipStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('NTHQA: partnership state', function (): void {
    test('NTHQA-FR-PART-004: fromModel bridges status and end date without persisting', function (): void {
        $model = new PartnershipStateModelDouble(['status' => PartnershipStatus::ACTIVE]);
        $model->setAttribute('end_date', Carbon::now()->addDays(10));

        $state = PartnershipState::fromModel($model);

        expect($state->isActive())->toBeTrue();
        expect($state->isExpiringSoon())->toBeTrue();
        expect($state->canBeDeleted())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('NTHQA-FR-PART-003: status predicates answer exactly one state', function (): void {
        $active = PartnershipState::fromArray(['status' => PartnershipStatus::ACTIVE, 'endDate' => null]);
        $expired = PartnershipState::fromArray(['status' => PartnershipStatus::EXPIRED, 'endDate' => null]);
        $terminated = PartnershipState::fromArray(['status' => PartnershipStatus::TERMINATED, 'endDate' => null]);

        expect($active->isActive())->toBeTrue();
        expect($active->isExpired())->toBeFalse();
        expect($active->isTerminated())->toBeFalse();
        expect($expired->isExpired())->toBeTrue();
        expect($expired->isActive())->toBeFalse();
        expect($terminated->isTerminated())->toBeTrue();
        expect($terminated->isActive())->toBeFalse();
    });

    test('NTHQA-FR-PART-004: isExpiringSoon trips only for active agreements near their end', function (): void {
        $soon = PartnershipState::fromArray(['status' => PartnershipStatus::ACTIVE, 'endDate' => Carbon::now()->addDays(10)->format('Y-m-d')]);
        $distant = PartnershipState::fromArray(['status' => PartnershipStatus::ACTIVE, 'endDate' => Carbon::now()->addDays(60)->format('Y-m-d')]);
        $past = PartnershipState::fromArray(['status' => PartnershipStatus::ACTIVE, 'endDate' => Carbon::now()->subDay()->format('Y-m-d')]);
        $dateless = PartnershipState::fromArray(['status' => PartnershipStatus::ACTIVE, 'endDate' => null]);
        $inactive = PartnershipState::fromArray(['status' => PartnershipStatus::EXPIRED, 'endDate' => Carbon::now()->addDays(10)->format('Y-m-d')]);

        expect($soon->isExpiringSoon())->toBeTrue();
        expect($soon->isExpiringSoon(5))->toBeFalse();
        expect($distant->isExpiringSoon())->toBeFalse();
        expect($past->isExpiringSoon())->toBeFalse();
        expect($dateless->isExpiringSoon())->toBeFalse();
        expect($inactive->isExpiringSoon())->toBeFalse();
    });

    test('NTHQA-FR-PART-006: canBeDeleted permits only terminal states', function (): void {
        expect(PartnershipState::fromArray(['status' => PartnershipStatus::ACTIVE, 'endDate' => null])->canBeDeleted())->toBeFalse();
        expect(PartnershipState::fromArray(['status' => PartnershipStatus::EXPIRED, 'endDate' => null])->canBeDeleted())->toBeTrue();
        expect(PartnershipState::fromArray(['status' => PartnershipStatus::TERMINATED, 'endDate' => null])->canBeDeleted())->toBeTrue();
    });

    test('NTHQA-FR-PART-004: fromArray rejects a missing status', function (): void {
        expect(fn (): PartnershipState => PartnershipState::fromArray(['endDate' => null]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('NTHQA-FR-PART-004: toArray, equals, and with round-trip by value', function (): void {
        $state = PartnershipState::fromArray(['status' => PartnershipStatus::ACTIVE, 'endDate' => null]);

        expect($state->toArray())->toBe(['status' => PartnershipStatus::ACTIVE, 'endDate' => null]);
        expect($state->equals(PartnershipState::fromArray(['status' => PartnershipStatus::ACTIVE, 'endDate' => null])))->toBeTrue();

        $expired = $state->with('status', PartnershipStatus::EXPIRED);

        expect($expired->canBeDeleted())->toBeTrue();
        expect($state->canBeDeleted())->toBeFalse();
    });
});
