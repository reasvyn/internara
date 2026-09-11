<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\AcademicYear\Entities\AcademicYearState;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

final class AcademicYearStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('XW6F5: academic year state', function (): void {
    test('XW6F5-FR-YEAR-003: fromArray hydrates flags with defaults', function (): void {
        $state = AcademicYearState::fromArray(['isActive' => true]);

        expect($state->isActive())->toBeTrue();
        expect($state->hasRelatedRecords())->toBeFalse();
    });

    test('XW6F5-FR-YEAR-003: fromArray rejects a missing isActive flag', function (): void {
        expect(fn (): AcademicYearState => AcademicYearState::fromArray(['hasRelatedRecords' => true]))
            ->toThrow(InvalidArgumentException::class, 'isActive');
    });

    test('XW6F5-FR-YEAR-003: fromModel resolves related records from loaded relations', function (): void {
        $model = new AcademicYearStateModelDouble(['is_active' => false]);
        $model->setRelation('internships', new EloquentCollection([new AcademicYearStateModelDouble]));
        $model->setRelation('assessments', new EloquentCollection);

        $state = AcademicYearState::fromModel($model);

        expect($state->isActive())->toBeFalse();
        expect($state->hasRelatedRecords())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('XW6F5-FR-YEAR-003: fromModel reports no related records when relations are empty', function (): void {
        $model = new AcademicYearStateModelDouble(['is_active' => true]);
        $model->setRelation('internships', new EloquentCollection);
        $model->setRelation('assessments', new EloquentCollection);

        $state = AcademicYearState::fromModel($model);

        expect($state->isActive())->toBeTrue();
        expect($state->hasRelatedRecords())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('XW6F5-FR-YEAR-006: canBeActivated refuses already-active years', function (): void {
        $active = AcademicYearState::fromArray(['isActive' => true]);
        $inactive = AcademicYearState::fromArray(['isActive' => false]);

        expect($active->canBeActivated())->toBeFalse();
        expect($inactive->canBeActivated())->toBeTrue();
    });

    test('XW6F5-FR-YEAR-007: canBeDeleted allows only inactive years without related records', function (): void {
        $deletable = AcademicYearState::fromArray(['isActive' => false, 'hasRelatedRecords' => false]);
        $active = AcademicYearState::fromArray(['isActive' => true, 'hasRelatedRecords' => false]);
        $referenced = AcademicYearState::fromArray(['isActive' => false, 'hasRelatedRecords' => true]);

        expect($deletable->canBeDeleted())->toBeTrue();
        expect($active->canBeDeleted())->toBeFalse();
        expect($referenced->canBeDeleted())->toBeFalse();
    });

    test('XW6F5-FR-YEAR-003: toArray, equals, and with round-trip by value', function (): void {
        $state = AcademicYearState::fromArray(['isActive' => false, 'hasRelatedRecords' => true]);

        expect($state->toArray())->toBe(['isActive' => false, 'hasRelatedRecords' => true]);
        expect($state->equals(AcademicYearState::fromArray(['isActive' => false, 'hasRelatedRecords' => true])))->toBeTrue();

        $activated = $state->with('isActive', true);

        expect($activated->isActive())->toBeTrue();
        expect($state->isActive())->toBeFalse();
        expect($state->equals($activated))->toBeFalse();
    });
});
