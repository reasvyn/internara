<?php

declare(strict_types=1);

use App\Modules\Program\Domain\Internship\Entities\InternshipState;
use Illuminate\Database\Eloquent\Model;

final class InternshipStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('7C5WM: internship state', function (): void {
    test('7C5WM-FR-LIFE-004: fromModel bridges relation counts without persisting', function (): void {
        $model = new InternshipStateModelDouble(['placements_count' => 0, 'registrations_count' => 3]);

        $state = InternshipState::fromModel($model);

        expect($state->canBeDeleted())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('7C5WM-FR-LIFE-004: fromModel defaults missing counts to zero', function (): void {
        $model = new InternshipStateModelDouble;

        $state = InternshipState::fromModel($model);

        expect($state->canBeDeleted())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('7C5WM-FR-LIFE-009: canBeDeleted blocks deletion above zero on either count', function (): void {
        expect(InternshipState::fromArray(['placementCount' => 0, 'registrationCount' => 0])->canBeDeleted())->toBeTrue();
        expect(InternshipState::fromArray(['placementCount' => 1, 'registrationCount' => 0])->canBeDeleted())->toBeFalse();
        expect(InternshipState::fromArray(['placementCount' => 0, 'registrationCount' => 1])->canBeDeleted())->toBeFalse();
    });

    test('7C5WM-FR-LIFE-004: fromArray rejects a missing placementCount', function (): void {
        expect(fn (): InternshipState => InternshipState::fromArray(['registrationCount' => 0]))
            ->toThrow(InvalidArgumentException::class, 'placementCount');
    });

    test('7C5WM-FR-LIFE-009: toArray, equals, and with round-trip by value', function (): void {
        $state = InternshipState::fromArray(['placementCount' => 0, 'registrationCount' => 0]);

        expect($state->toArray())->toBe(['placementCount' => 0, 'registrationCount' => 0]);
        expect($state->equals(InternshipState::fromArray(['placementCount' => 0, 'registrationCount' => 0])))->toBeTrue();

        $referenced = $state->with('registrationCount', 2);

        expect($referenced->canBeDeleted())->toBeFalse();
        expect($state->canBeDeleted())->toBeTrue();
    });
});
