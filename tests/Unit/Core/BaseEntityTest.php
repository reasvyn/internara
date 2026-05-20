<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class TestEntity extends BaseEntity
{
    public function __construct(
        private string $value,
        private int $count,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            value: $model->getAttribute('value') ?? '',
            count: (int) ($model->getAttribute('count') ?? 0),
        );
    }

    public function value(): string
    {
        return $this->value;
    }

    public function count(): int
    {
        return $this->count;
    }
}

class TestModel extends Model
{
    protected $attributes = ['value' => 'test-value', 'count' => 42];
}

describe('BaseEntity', function () {
    it('creates from model via fromModel', function () {
        $model = new TestModel;
        $entity = TestEntity::fromModel($model);

        expect($entity)->toBeInstanceOf(TestEntity::class)
            ->and($entity->value())->toBe('test-value')
            ->and($entity->count())->toBe(42);
    });

    it('is readonly', function () {
        $ref = new ReflectionClass(TestEntity::class);

        expect($ref->isReadOnly())->toBeTrue();
    });

    it('is final', function () {
        $ref = new ReflectionClass(TestEntity::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('is instantiable directly without database', function () {
        $entity = new TestEntity(value: 'direct', count: 10);

        expect($entity->value())->toBe('direct')
            ->and($entity->count())->toBe(10);
    });
});
