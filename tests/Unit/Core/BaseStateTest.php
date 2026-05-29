<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Core\States\BaseState;
use Illuminate\Database\Eloquent\Model;

final readonly class TestStateEntity extends BaseState
{
    public function __construct(
        public string $status = 'pending',
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self($model->status ?? 'pending');
    }
}

describe('BaseState', function () {
    it('extends BaseEntity', function () {
        $reflection = new ReflectionClass(BaseState::class);

        expect($reflection->isAbstract())->toBeTrue()
            ->and($reflection->isReadOnly())->toBeTrue()
            ->and($reflection->getParentClass()->getName())->toBe(BaseEntity::class);
    });

    it('checks exact state via isState', function () {
        $entity = new TestStateEntity('active');

        expect($entity->isState('active'))->toBeTrue()
            ->and($entity->isState('pending'))->toBeFalse();
    });

    it('checks state membership via isStateIn', function () {
        $entity = new TestStateEntity('active');

        expect($entity->isStateIn(['active', 'completed']))->toBeTrue()
            ->and($entity->isStateIn(['pending', 'draft']))->toBeFalse();
    });

    it('returns false for isState when status property is missing', function () {
        $entity = new class extends BaseState
        {
            public function __construct() {}

            public static function fromModel(Model $model): static
            {
                return new self;
            }
        };

        expect($entity->isState('anything'))->toBeFalse();
    });
});
