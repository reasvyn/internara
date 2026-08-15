<?php

declare(strict_types=1);

use App\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class TestEntity extends BaseEntity
{
    public function __construct(
        public string $name,
        public int $age = 0,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            name: (string) $model->getAttribute('name'),
            age: (int) $model->getAttribute('age'),
        );
    }
}

it('SE5Q9-FR-M3: cannot be instantiated directly (abstract)', function () {
    expect((new ReflectionClass(BaseEntity::class))->isAbstract())->toBeTrue();
});

it('SE5Q9-FR-M3: fromArray() builds the entity from constructor params', function () {
    $entity = TestEntity::fromArray(['name' => 'Adit', 'age' => 18]);

    expect($entity->name)->toBe('Adit');
    expect($entity->age)->toBe(18);
});

it('SE5Q9-FR-M3: fromArray() throws when a required param is missing', function () {
    TestEntity::fromArray(['age' => 18]);
})->throws(InvalidArgumentException::class, 'Missing required constructor parameter "name"');

it('SE5Q9-FR-M3: fromModel() bridges a model to the entity', function () {
    $model = new class extends Model
    {
        protected $table = 'test_entities';

        protected $guarded = [];
    };
    $model->forceFill(['name' => 'Adit', 'age' => 18]);

    $entity = TestEntity::fromModel($model);

    expect($entity->name)->toBe('Adit');
    expect($entity->age)->toBe(18);
});

it('SE5Q9-FR-M3: toArray() and jsonSerialize() expose the entity values', function () {
    $entity = TestEntity::fromArray(['name' => 'Adit', 'age' => 18]);

    expect($entity->toArray())->toBe(['name' => 'Adit', 'age' => 18]);
    expect($entity->jsonSerialize())->toBe(['name' => 'Adit', 'age' => 18]);
});

it('SE5Q9-FR-M3: equals() compares value, with() returns a modified copy', function () {
    $entity = TestEntity::fromArray(['name' => 'Adit', 'age' => 18]);

    expect($entity->equals(TestEntity::fromArray(['name' => 'Adit', 'age' => 18])))->toBeTrue();
    expect($entity->equals(TestEntity::fromArray(['name' => 'Other', 'age' => 18])))->toBeFalse();

    $updated = $entity->with('age', 19);

    expect($updated->age)->toBe(19);
    expect($entity->age)->toBe(18);
});
