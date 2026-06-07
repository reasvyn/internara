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

readonly class NestedMockData extends BaseData
{
    public function __construct(public string $label, public MockData $child) {}
}

readonly class MockDataWithValue extends BaseData
{
    public function __construct(public string $value) {}
}

readonly class MockDtoWithToArray
{
    public function __construct(public string $value) {}

    public function toArray(): array
    {
        return ['value' => $this->value];
    }
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
    expect(
        fn () => MockData::fromArray([
            'name' => 'John',
        ]),
    )->toThrow(\InvalidArgumentException::class);
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

test('it handles nested base data objects in to array recursively', function () {
    $child = new MockData('Alice', 25);
    $parent = new NestedMockData('Parent', $child);

    $arr = $parent->toArray();

    expect($arr)->toBe([
        'label' => 'Parent',
        'child' => [
            'name' => 'Alice',
            'age' => 25,
            'isAdmin' => false,
        ],
    ]);
});

test('from method accepts array source', function () {
    $data = MockData::from([
        'name' => 'Array Source',
        'age' => 99,
    ]);

    expect($data->name)->toBe('Array Source');
    expect($data->age)->toBe(99);
});

test('from method accepts object with to array method', function () {
    $dto = new MockDtoWithToArray('Object Value');
    $data = MockDataWithValue::from($dto);

    expect($data)->toBeInstanceOf(MockDataWithValue::class);
    expect($data->value)->toBe('Object Value');
});

test('from method throws on unsupported source type', function () {
    expect(fn () => MockData::from(42))->toThrow(
        \InvalidArgumentException::class,
        'Unsupported source type',
    );
});

test('from method throws on null source', function () {
    expect(fn () => MockData::from(null))->toThrow(
        \InvalidArgumentException::class,
        'Unsupported source type',
    );
});

test('to array preserves null values', function () {
    $dto = new MockData('Null Value', 0, false);
    $arr = $dto->toArray();

    expect($arr['name'])->toBe('Null Value');
    expect($arr['age'])->toBe(0);
});
