<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Placement\Entities\PlacementCapacity;
use Illuminate\Database\Eloquent\Model;

final class PlacementCapacityModelDouble extends Model
{
    protected $guarded = [];
}

describe('J9GBH: placement capacity', function (): void {
    test('J9GBH-FR-PLACE-004: fromModel bridges quota columns without persisting', function (): void {
        $model = new PlacementCapacityModelDouble(['quota' => 5, 'filled_quota' => 4]);

        $capacity = PlacementCapacity::fromModel($model);

        expect($capacity->isFull())->toBeFalse();
        expect($capacity->availableSlots())->toBe(1);
        expect($capacity->hasAvailableSlots())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('J9GBH-FR-PLACE-003: fromArray hydrates quotas and rejects missing params', function (): void {
        $capacity = PlacementCapacity::fromArray(['quota' => 1, 'filledQuota' => 0]);

        expect($capacity->hasAvailableSlots())->toBeTrue();

        expect(fn (): PlacementCapacity => PlacementCapacity::fromArray(['quota' => 1]))
            ->toThrow(InvalidArgumentException::class, 'filledQuota');
    });

    test('J9GBH-FR-PLACE-004: isFull trips exactly at quota and stays tripped past it', function (): void {
        expect(PlacementCapacity::fromArray(['quota' => 5, 'filledQuota' => 4])->isFull())->toBeFalse();
        expect(PlacementCapacity::fromArray(['quota' => 5, 'filledQuota' => 5])->isFull())->toBeTrue();
        expect(PlacementCapacity::fromArray(['quota' => 5, 'filledQuota' => 6])->isFull())->toBeTrue();
        expect(PlacementCapacity::fromArray(['quota' => 0, 'filledQuota' => 0])->isFull())->toBeTrue();
    });

    test('J9GBH-FR-PLACE-004: availableSlots never drops below zero', function (): void {
        expect(PlacementCapacity::fromArray(['quota' => 5, 'filledQuota' => 2])->availableSlots())->toBe(3);
        expect(PlacementCapacity::fromArray(['quota' => 5, 'filledQuota' => 5])->availableSlots())->toBe(0);
        expect(PlacementCapacity::fromArray(['quota' => 5, 'filledQuota' => 9])->availableSlots())->toBe(0);
    });

    test('J9GBH-FR-PLACE-004: hasAvailableSlots mirrors the remaining count', function (): void {
        expect(PlacementCapacity::fromArray(['quota' => 3, 'filledQuota' => 2])->hasAvailableSlots())->toBeTrue();
        expect(PlacementCapacity::fromArray(['quota' => 3, 'filledQuota' => 3])->hasAvailableSlots())->toBeFalse();
    });

    test('J9GBH-FR-PLACE-004: toArray, equals, and with round-trip by value', function (): void {
        $capacity = PlacementCapacity::fromArray(['quota' => 5, 'filledQuota' => 4]);

        expect($capacity->toArray())->toBe(['quota' => 5, 'filledQuota' => 4]);
        expect($capacity->equals(PlacementCapacity::fromArray(['quota' => 5, 'filledQuota' => 4])))->toBeTrue();

        $filled = $capacity->with('filledQuota', 5);

        expect($filled->isFull())->toBeTrue();
        expect($capacity->isFull())->toBeFalse();
    });
});
