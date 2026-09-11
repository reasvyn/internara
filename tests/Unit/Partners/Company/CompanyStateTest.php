<?php

declare(strict_types=1);

use App\Modules\Partners\Domain\Company\Entities\CompanyState;
use Illuminate\Database\Eloquent\Model;

final class CompanyStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('XI3LB: company state', function (): void {
    test('XI3LB-FR-COMP-004: fromModel bridges relation-count aggregates without persisting', function (): void {
        $model = new CompanyStateModelDouble(['placements_count' => 1, 'partnerships_count' => 0]);

        $state = CompanyState::fromModel($model);

        expect($state->canBeDeleted())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('XI3LB-FR-COMP-004: fromModel defaults missing aggregates to zero', function (): void {
        $model = new CompanyStateModelDouble;

        $state = CompanyState::fromModel($model);

        expect($state->canBeDeleted())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('XI3LB-FR-COMP-003: fromArray rejects a missing placementCount', function (): void {
        expect(fn (): CompanyState => CompanyState::fromArray(['partnershipCount' => 0]))
            ->toThrow(InvalidArgumentException::class, 'placementCount');
    });

    test('XI3LB-FR-COMP-005: canBeDeleted needs zero placements and zero partnerships', function (): void {
        expect(CompanyState::fromArray(['placementCount' => 0, 'partnershipCount' => 0])->canBeDeleted())->toBeTrue();
        expect(CompanyState::fromArray(['placementCount' => 1, 'partnershipCount' => 0])->canBeDeleted())->toBeFalse();
        expect(CompanyState::fromArray(['placementCount' => 0, 'partnershipCount' => 1])->canBeDeleted())->toBeFalse();
        expect(CompanyState::fromArray(['placementCount' => 2, 'partnershipCount' => 3])->canBeDeleted())->toBeFalse();
    });

    test('XI3LB-FR-COMP-005: toArray, equals, and with round-trip by value', function (): void {
        $state = CompanyState::fromArray(['placementCount' => 0, 'partnershipCount' => 0]);

        expect($state->toArray())->toBe(['placementCount' => 0, 'partnershipCount' => 0]);
        expect($state->equals(CompanyState::fromArray(['placementCount' => 0, 'partnershipCount' => 0])))->toBeTrue();

        $referenced = $state->with('placementCount', 1);

        expect($referenced->canBeDeleted())->toBeFalse();
        expect($state->canBeDeleted())->toBeTrue();
    });
});
