<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Placement\Entities\PlacementState;
use Illuminate\Database\Eloquent\Model;

final class PlacementStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('J9GBH: placement state', function (): void {
    test('J9GBH-FR-PLACE-005: fromModel bridges the registration count without persisting', function (): void {
        $model = new PlacementStateModelDouble(['registrations_count' => 2]);

        $state = PlacementState::fromModel($model);

        expect($state->canBeDeleted())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('J9GBH-FR-PLACE-005: fromModel defaults a missing count to zero', function (): void {
        $model = new PlacementStateModelDouble;

        $state = PlacementState::fromModel($model);

        expect($state->canBeDeleted())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('J9GBH-FR-PLACE-005: fromArray rejects a missing registrationCount', function (): void {
        expect(fn (): PlacementState => PlacementState::fromArray([]))
            ->toThrow(InvalidArgumentException::class, 'registrationCount');
    });

    test('J9GBH-FR-PLACE-006: canBeDeleted is true only when the count is zero', function (): void {
        expect(PlacementState::fromArray(['registrationCount' => 0])->canBeDeleted())->toBeTrue();
        expect(PlacementState::fromArray(['registrationCount' => 1])->canBeDeleted())->toBeFalse();
        expect(PlacementState::fromArray(['registrationCount' => 12])->canBeDeleted())->toBeFalse();
    });

    test('J9GBH-FR-PLACE-005: toArray, equals, and with round-trip by value', function (): void {
        $state = PlacementState::fromArray(['registrationCount' => 0]);

        expect($state->toArray())->toBe(['registrationCount' => 0]);
        expect($state->equals(PlacementState::fromArray(['registrationCount' => 0])))->toBeTrue();

        $referenced = $state->with('registrationCount', 1);

        expect($referenced->canBeDeleted())->toBeFalse();
        expect($state->canBeDeleted())->toBeTrue();
    });
});
