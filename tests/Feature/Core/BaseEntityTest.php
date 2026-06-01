<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class TestEntity extends BaseEntity
{
    public function __construct(
        public string $name,
        public int $value,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            name: $model->name,
            value: $model->value,
        );
    }
}

class TestEntityModel extends Model
{
    protected $table = 'test_entities';

    public string $name = 'test';

    public int $value = 42;
}

describe('BaseEntity', function () {
    it('is abstract and readonly', function () {
        $ref = new ReflectionClass(BaseEntity::class);

        expect($ref->isAbstract())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('creates from model via fromModel bridge', function () {
        $model = new TestEntityModel;
        $model->name = 'from-db';
        $model->value = 99;

        $entity = TestEntity::fromModel($model);

        expect($entity->name)->toBe('from-db')
            ->and($entity->value)->toBe(99);
    });
});
