<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Entities;

use App\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;
use Mockery;

readonly class MockEntity extends BaseEntity
{
    public function __construct(public mixed $id) {}

    public static function fromModel(Model $model): static
    {
        return new static($model->getKey());
    }
}

test('base entity can be instantiated from model', function () {
    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getKey')->andReturn(456);

    $entity = MockEntity::fromModel($model);

    expect($entity->id)->toBe(456);
});
