<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

test('BaseEntity is abstract and readonly', function () {
    $ref = new ReflectionClass(BaseEntity::class);
    expect($ref->isAbstract())->toBeTrue();
    expect($ref->isReadOnly())->toBeTrue();
});

test('BaseEntity requires fromModel static method', function () {
    $ref = new ReflectionClass(BaseEntity::class);
    expect($ref->hasMethod('fromModel'))->toBeTrue();
    expect($ref->getMethod('fromModel')->isAbstract())->toBeTrue();
    expect($ref->getMethod('fromModel')->isStatic())->toBeTrue();
    expect($ref->getMethod('fromModel')->getParameters()[0]->getType()->getName())
        ->toBe(Model::class);
});

test('BaseEntity can be extended by concrete entity', function () {
    $entity = new readonly class(fake()->name()) extends BaseEntity
    {
        public function __construct(
            public readonly string $name,
        ) {}

        public static function fromModel(Model $model): static
        {
            return new self($model->getAttribute('name'));
        }
    };

    expect($entity)->toBeInstanceOf(BaseEntity::class);
    expect($entity->name)->toBeString();
});
