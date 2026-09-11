<?php

declare(strict_types=1);

use App\Modules\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class EntityTestDouble extends BaseEntity
{
    public function __construct(
        public string $id,
        public string $name,
        public float $score = 0.0,
    ) {}

    public static function fromModel(Model $model): static
    {
        $attributes = $model->getAttributes();

        return new self(
            id: (string) ($attributes['id'] ?? ''),
            name: (string) ($attributes['name'] ?? ''),
            score: (float) ($attributes['score'] ?? 0.0),
        );
    }
}

final class AnonymousModelDouble extends Model
{
    protected $guarded = [];
}

describe('SE5Q9: base entity', function (): void {
    test('SE5Q9-FR-BASE-011: fromArray hydrates constructor params with defaults', function (): void {
        $entity = EntityTestDouble::fromArray(['id' => '1', 'name' => 'Placement']);

        expect($entity->id)->toBe('1');
        expect($entity->name)->toBe('Placement');
        expect($entity->score)->toBe(0.0);
    });

    test('SE5Q9-FR-BASE-011: fromArray rejects a missing required param', function (): void {
        expect(fn (): EntityTestDouble => EntityTestDouble::fromArray(['id' => '1']))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('QLHDO-FR-GLB-016: fromModel bridges persistence without persisting', function (): void {
        $model = new AnonymousModelDouble(['id' => '7', 'name' => 'Bridged', 'score' => 4.5]);

        $entity = EntityTestDouble::fromModel($model);

        expect($entity->id)->toBe('7');
        expect($entity->name)->toBe('Bridged');
        expect($entity->score)->toBe(4.5);
        expect($model->exists)->toBeFalse();
    });

    test('SE5Q9-FR-BASE-011: toArray and jsonSerialize expose plain state', function (): void {
        $entity = new EntityTestDouble(id: '1', name: 'Placement', score: 9.5);

        expect($entity->toArray())->toBe(['id' => '1', 'name' => 'Placement', 'score' => 9.5]);
        expect($entity->jsonSerialize())->toBe($entity->toArray());
        expect(json_encode($entity))->toBeJson();
    });

    test('SE5Q9-FR-BASE-011: equals compares by value, with() returns a modified copy', function (): void {
        $a = new EntityTestDouble(id: '1', name: 'Placement');
        $b = new EntityTestDouble(id: '1', name: 'Placement');
        $c = $a->with('name', 'Renamed');

        expect($a->equals($b))->toBeTrue();
        expect($a->equals($c))->toBeFalse();
        expect($c->name)->toBe('Renamed');
        expect($a->name)->toBe('Placement');
    });
});
