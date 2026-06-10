<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Entities;

use App\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

readonly class MockEntity extends BaseEntity
{
    public function __construct(public mixed $id, public string $name = 'default') {}

    public static function fromModel(Model $model): static
    {
        return new static(id: $model->getKey(), name: $model->name ?? 'unknown');
    }
}

function createTestModel(mixed $key = 456, ?string $name = null): Model
{
    $model = new class extends Model
    {
        protected $table = 'test';

        public mixed $testKey;

        public ?string $testName = null;

        public function getKey(): mixed
        {
            return $this->testKey;
        }

        public function getAttribute($key): mixed
        {
            if ($key === 'name') {
                return $this->testName;
            }

            return parent::getAttribute($key);
        }
    };

    $model->testKey = $key;
    $model->testName = $name;

    return $model;
}

test('base entity can be instantiated from model', function () {
    $model = createTestModel(456, 'Test Entity');

    $entity = MockEntity::fromModel($model);

    expect($entity->id)->toBe(456);
    expect($entity->name)->toBe('Test Entity');
});

test('base entity is readonly and immutable', function () {
    $model = createTestModel(1, 'Original');

    $entity = MockEntity::fromModel($model);

    $reflection = new \ReflectionClass($entity);
    $properties = $reflection->getProperties();

    foreach ($properties as $prop) {
        expect($prop->isReadOnly())->toBeTrue("Property \${$prop->getName()} should be readonly");
    }
});

test('base entity preserves model data accurately', function () {
    $model = createTestModel(789, 'Accurate Data');

    $entity = MockEntity::fromModel($model);

    expect($entity->id)->toBe(789);
    expect($entity->name)->toBe('Accurate Data');
});

test('base entity from model uses defaults for missing attributes', function () {
    $model = createTestModel(1);

    $entity = MockEntity::fromModel($model);

    expect($entity->name)->toBe('unknown');
});

test('base entity class is abstract', function () {
    $reflection = new \ReflectionClass(BaseEntity::class);

    expect($reflection->isAbstract())->toBeTrue();
});

test('base entity class is readonly', function () {
    $reflection = new \ReflectionClass(BaseEntity::class);

    expect($reflection->isReadOnly())->toBeTrue();
});

test('base entity from model returns correct static type', function () {
    $model = createTestModel(1);

    $entity = MockEntity::fromModel($model);

    expect($entity)->toBeInstanceOf(MockEntity::class);
    expect($entity)->toBeInstanceOf(BaseEntity::class);
});

test('base entity serializes to array', function () {
    $entity = new MockEntity(id: 42, name: 'Test');

    $array = $entity->toArray();

    expect($array)->toBe(['id' => 42, 'name' => 'Test']);
});

test('base entity implements json serialize', function () {
    $entity = new MockEntity(id: 7, name: 'Json');

    $serialized = json_decode(json_encode($entity), true);

    expect($serialized)->toBe(['id' => 7, 'name' => 'Json']);
});

test('base entity equals returns true for same values', function () {
    $a = new MockEntity(id: 1, name: 'Same');
    $b = new MockEntity(id: 1, name: 'Same');

    expect($a->equals($b))->toBeTrue();
});

test('base entity equals returns false for different values', function () {
    $a = new MockEntity(id: 1, name: 'A');
    $b = new MockEntity(id: 2, name: 'B');

    expect($a->equals($b))->toBeFalse();
});

test('base entity equals returns true for same instance', function () {
    $entity = new MockEntity(id: 1, name: 'Test');

    expect($entity->equals($entity))->toBeTrue();
});

test('base entity with creates new instance with changed property', function () {
    $entity = new MockEntity(id: 1, name: 'Original');

    $modified = $entity->with('name', 'Modified');

    expect($entity->name)->toBe('Original');
    expect($modified->name)->toBe('Modified');
    expect($modified->id)->toBe(1);
    expect($modified)->not->toBe($entity);
});

test('base entity with preserves type', function () {
    $entity = new MockEntity(id: 1, name: 'Original');

    $modified = $entity->with('name', 'Modified');

    expect($modified)->toBeInstanceOf(MockEntity::class);
});

test('base entity from array creates instance', function () {
    $entity = MockEntity::fromArray(['id' => 99, 'name' => 'FromArray']);

    expect($entity)->toBeInstanceOf(MockEntity::class);
    expect($entity->id)->toBe(99);
    expect($entity->name)->toBe('FromArray');
});

test('base entity from array uses defaults for missing params', function () {
    $entity = MockEntity::fromArray(['id' => 1]);

    expect($entity->name)->toBe('default');
});

test('base entity from model works without custom attributes', function () {
    $model = createTestModel(999);

    $entity = MockEntity::fromModel($model);

    expect($entity->id)->toBe(999);
});
