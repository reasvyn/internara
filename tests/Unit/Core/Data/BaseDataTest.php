<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Data;

use App\Core\Data\BaseData;

readonly class MockData extends BaseData
{
    public function __construct(
        public string $name,
        public int $age,
        public bool $isAdmin = false,
    ) {}
}

test('it can hydrate from array with exact keys', function () {
    $data = MockData::fromArray([
        'name' => 'John Doe',
        'age' => 30,
        'isAdmin' => true,
    ]);

    expect($data->name)->toBe('John Doe');
    expect($data->age)->toBe(30);
    expect($data->isAdmin)->toBeTrue();
});

test('it can hydrate from array with snake_case keys', function () {
    $data = MockData::fromArray([
        'name' => 'Jane Doe',
        'age' => 25,
        'is_admin' => true,
    ]);

    expect($data->name)->toBe('Jane Doe');
    expect($data->age)->toBe(25);
    expect($data->isAdmin)->toBeTrue();
});

test('it throws exception on missing required params', function () {
    expect(fn () => MockData::fromArray([
        'name' => 'John',
    ]))->toThrow(\InvalidArgumentException::class);
});

test('it uses default values when available', function () {
    $data = MockData::fromArray([
        'name' => 'John',
        'age' => 40,
    ]);

    expect($data->isAdmin)->toBeFalse();
});

test('it can serialize to array', function () {
    $dto = new MockData('Bob', 50, true);
    $arr = $dto->toArray();

    expect($arr)->toBe([
        'name' => 'Bob',
        'age' => 50,
        'isAdmin' => true,
    ]);
});
