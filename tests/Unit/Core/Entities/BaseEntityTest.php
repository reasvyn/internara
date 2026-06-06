<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Entities;

use App\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;
use Mockery;

readonly class MockEntity extends BaseEntity
{
    public function __construct(
        public mixed $id,
        public string $name = 'default',
    ) {}

    public static function fromModel(Model $model): static
    {
        return new static(
            id: $model->getKey(),
            name: $model->name ?? 'unknown',
        );
    }
}

function createModelMock(mixed $key = 456, ?string $name = null): Model
{
    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getKey')->andReturn($key);
    $model->shouldReceive('getAttribute')->with('name')->andReturn($name);
    $model->shouldReceive('offsetExists')->andReturn(true);

    return $model;
}

test('base entity can be instantiated from model', function () {
    $model = createModelMock(456, 'Test Entity');

    $entity = MockEntity::fromModel($model);

    expect($entity->id)->toBe(456);
    expect($entity->name)->toBe('Test Entity');
});

test('base entity is readonly and immutable', function () {
    $model = createModelMock(1, 'Original');

    $entity = MockEntity::fromModel($model);

    $reflection = new \ReflectionClass($entity);
    $properties = $reflection->getProperties();

    foreach ($properties as $prop) {
        expect($prop->isReadOnly())->toBeTrue("Property \${$prop->getName()} should be readonly");
    }
});

test('base entity preserves model data accurately', function () {
    $model = createModelMock(789, 'Accurate Data');

    $entity = MockEntity::fromModel($model);

    expect($entity->id)->toBe(789);
    expect($entity->name)->toBe('Accurate Data');
});

test('base entity from model uses defaults for missing attributes', function () {
    $model = createModelMock(1);

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
    $model = createModelMock(1);

    $entity = MockEntity::fromModel($model);

    expect($entity)->toBeInstanceOf(MockEntity::class);
    expect($entity)->toBeInstanceOf(BaseEntity::class);
});
