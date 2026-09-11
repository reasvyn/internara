<?php

declare(strict_types=1);

use App\Modules\Program\Domain\InternshipGroup\Entities\InternshipGroupState;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

final class InternshipGroupStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('IT0OE: internship group state', function (): void {
    test('IT0OE-FR-GROUP-004: fromModel bridges member count and flag without persisting', function (): void {
        $model = new InternshipGroupStateModelDouble(['is_active' => true]);
        $model->setRelation('members', new EloquentCollection([
            new InternshipGroupStateModelDouble,
            new InternshipGroupStateModelDouble,
        ]));

        $state = InternshipGroupState::fromModel($model);

        expect($state->isActive())->toBeTrue();
        expect($state->hasMembers())->toBeTrue();
        expect($state->canBeDeleted())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('IT0OE-FR-GROUP-006: fromModel reports an inactive empty group', function (): void {
        $model = new InternshipGroupStateModelDouble(['is_active' => false]);
        $model->setRelation('members', new EloquentCollection);

        $state = InternshipGroupState::fromModel($model);

        expect($state->isActive())->toBeFalse();
        expect($state->hasMembers())->toBeFalse();
        expect($state->canBeDeleted())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('IT0OE-FR-GROUP-005: canBeDeleted is false whenever members exist', function (): void {
        expect(InternshipGroupState::fromArray(['memberCount' => 0, 'isActive' => true])->canBeDeleted())->toBeTrue();
        expect(InternshipGroupState::fromArray(['memberCount' => 1, 'isActive' => false])->canBeDeleted())->toBeFalse();
    });

    test('IT0OE-FR-GROUP-003: fromArray rejects a missing memberCount', function (): void {
        expect(fn (): InternshipGroupState => InternshipGroupState::fromArray(['isActive' => true]))
            ->toThrow(InvalidArgumentException::class, 'memberCount');
    });

    test('IT0OE-FR-GROUP-004: toArray, equals, and with round-trip by value', function (): void {
        $state = InternshipGroupState::fromArray(['memberCount' => 0, 'isActive' => true]);

        expect($state->toArray())->toBe(['memberCount' => 0, 'isActive' => true]);
        expect($state->equals(InternshipGroupState::fromArray(['memberCount' => 0, 'isActive' => true])))->toBeTrue();

        $joined = $state->with('memberCount', 4);

        expect($joined->hasMembers())->toBeTrue();
        expect($state->hasMembers())->toBeFalse();
    });
});
