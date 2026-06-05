<?php

declare(strict_types=1);

use App\Core\Data\BaseData;

test('BaseData toArray returns all public properties', function () {
    $dto = new readonly class('John', 'john@test.com') extends BaseData
    {
        public function __construct(
            public readonly string $name,
            public readonly string $email,
        ) {}
    };

    expect($dto->toArray())->toBe([
        'name' => 'John',
        'email' => 'john@test.com',
    ]);
});

test('BaseData toArray handles nested DTOs recursively', function () {
    $nested = new readonly class('Nested') extends BaseData
    {
        public function __construct(
            public readonly string $value,
        ) {}
    };

    $parent = new readonly class($nested) extends BaseData
    {
        public function __construct(
            public readonly BaseData $child,
        ) {}
    };

    $result = $parent->toArray();
    expect($result['child'])->toBe(['value' => 'Nested']);
});

test('BaseData fromArray creates instance from camelCase keys', function () {
    $dto = (new readonly class('') extends BaseData
    {
        public function __construct(
            public readonly string $firstName,
        ) {}
    })::fromArray(['firstName' => 'John']);

    expect($dto->firstName)->toBe('John');
});

test('BaseData fromArray creates instance from snake_case keys', function () {
    $dto = (new readonly class('') extends BaseData
    {
        public function __construct(
            public readonly string $firstName,
        ) {}
    })::fromArray(['first_name' => 'Jane']);

    expect($dto->firstName)->toBe('Jane');
});

test('BaseData fromArray throws on missing required parameter', function () {
    (new readonly class('', '') extends BaseData
    {
        public function __construct(
            public readonly string $required,
            public readonly string $alsoRequired,
        ) {}
    })::fromArray(['required' => 'val']);
})->throws(InvalidArgumentException::class);

test('BaseData from converts array', function () {
    $dto = (new readonly class('') extends BaseData
    {
        public function __construct(
            public readonly string $name,
        ) {}
    })::from(['name' => 'Test']);

    expect($dto->name)->toBe('Test');
});

test('BaseData from throws on unsupported type', function () {
    (new readonly class('') extends BaseData
    {
        public function __construct(
            public readonly string $name,
        ) {}
    })::from(42);
})->throws(InvalidArgumentException::class);

test('BaseData is abstract and readonly', function () {
    $ref = new ReflectionClass(BaseData::class);
    expect($ref->isAbstract())->toBeTrue();
    expect($ref->isReadOnly())->toBeTrue();
});
