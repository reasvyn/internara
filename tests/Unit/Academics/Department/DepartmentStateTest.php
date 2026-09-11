<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\Department\Entities\DepartmentState;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

final class DepartmentStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('4HWSB: department state', function (): void {
    test('4HWSB-FR-DEPT-003: fromArray hydrates counts with no defaults to fall back on', function (): void {
        $state = DepartmentState::fromArray(['profileCount' => 2, 'hasProfiles' => true]);

        expect($state->toArray())->toBe(['profileCount' => 2, 'hasProfiles' => true]);
    });

    test('4HWSB-FR-DEPT-003: fromArray rejects a missing profileCount', function (): void {
        expect(fn (): DepartmentState => DepartmentState::fromArray(['hasProfiles' => false]))
            ->toThrow(InvalidArgumentException::class, 'profileCount');
    });

    test('4HWSB-FR-DEPT-004: fromModel resolves counts from the eager-loaded relation', function (): void {
        $model = new DepartmentStateModelDouble(['profiles_count' => 2]);
        $model->setRelation('profiles', new EloquentCollection([
            new DepartmentStateModelDouble,
            new DepartmentStateModelDouble,
        ]));

        $state = DepartmentState::fromModel($model);

        expect($state->toArray())->toBe(['profileCount' => 2, 'hasProfiles' => true]);
        expect($model->exists)->toBeFalse();
    });

    test('4HWSB-FR-DEPT-004: fromModel reports zero profiles when the relation is empty', function (): void {
        $model = new DepartmentStateModelDouble(['profiles_count' => 0]);
        $model->setRelation('profiles', new EloquentCollection);

        $state = DepartmentState::fromModel($model);

        expect($state->toArray())->toBe(['profileCount' => 0, 'hasProfiles' => false]);
        expect($model->exists)->toBeFalse();
    });

    test('4HWSB-FR-DEPT-005: canBeDeleted is false whenever any profile is attached', function (): void {
        $withProfiles = DepartmentState::fromArray(['profileCount' => 1, 'hasProfiles' => true]);
        $withoutProfiles = DepartmentState::fromArray(['profileCount' => 0, 'hasProfiles' => false]);

        expect($withProfiles->canBeDeleted())->toBeFalse();
        expect($withoutProfiles->canBeDeleted())->toBeTrue();
    });

    test('4HWSB-FR-DEPT-005: equals and with compare by value', function (): void {
        $state = DepartmentState::fromArray(['profileCount' => 0, 'hasProfiles' => false]);

        expect($state->equals(DepartmentState::fromArray(['profileCount' => 0, 'hasProfiles' => false])))->toBeTrue();

        $blocked = $state->with('hasProfiles', true);

        expect($blocked->canBeDeleted())->toBeFalse();
        expect($state->canBeDeleted())->toBeTrue();
    });
});
