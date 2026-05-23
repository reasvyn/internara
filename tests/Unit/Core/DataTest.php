<?php

declare(strict_types=1);

use App\Domain\Core\Data\Data;

final readonly class TestData extends Data
{
    public function __construct(
        public string $name,
        public int $count = 0,
        public ?string $label = null,
    ) {}
}

final readonly class NestedData extends Data
{
    public function __construct(
        public TestData $child,
        public string $title,
    ) {}
}

describe('Data DTO', function () {
    it('converts to array', function () {
        $dto = new TestData(name: 'test', count: 5, label: 'hello');

        $result = $dto->toArray();

        expect($result)->toBe([
            'name' => 'test',
            'count' => 5,
            'label' => 'hello',
        ]);
    });

    it('creates from array', function () {
        $dto = TestData::fromArray([
            'name' => 'from-array',
            'count' => 10,
        ]);

        expect($dto)->toBeInstanceOf(TestData::class)
            ->and($dto->name)->toBe('from-array')
            ->and($dto->count)->toBe(10)
            ->and($dto->label)->toBeNull();
    });

    it('creates from array with snake_case keys', function () {
        $dto = TestData::fromArray([
            'name' => 'snake',
            'count' => 3,
        ]);

        expect($dto->name)->toBe('snake');
    });

    it('uses defaults when keys are missing', function () {
        $dto = TestData::fromArray(['name' => 'defaults']);

        expect($dto->count)->toBe(0)
            ->and($dto->label)->toBeNull();
    });

    it('converts nested Data objects recursively', function () {
        $child = new TestData(name: 'child', count: 1);
        $parent = new NestedData(child: $child, title: 'parent');

        $result = $parent->toArray();

        expect($result)->toBe([
            'child' => ['name' => 'child', 'count' => 1, 'label' => null],
            'title' => 'parent',
        ]);
    });

    it('creates from mixed source with array input', function () {
        $dto = TestData::from(['name' => 'mixed', 'count' => 7]);

        expect($dto)->toBeInstanceOf(TestData::class)
            ->and($dto->name)->toBe('mixed')
            ->and($dto->count)->toBe(7);
    });

    it('throws for unsupported source type', function () {
        TestData::from('invalid');
    })->throws(InvalidArgumentException::class);

    it('throws when required parameter is missing in fromArray', function () {
        TestData::fromArray(['count' => 5]);
    })->throws(InvalidArgumentException::class, 'Missing required constructor parameter "name"');
});
