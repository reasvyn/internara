<?php

declare(strict_types=1);

use App\Domain\Core\Data\Data;

final readonly class TestDto extends Data
{
    public function __construct(
        public string $name,
        public int $count = 0,
        public ?string $label = null,
    ) {}
}

final readonly class NestedDto extends Data
{
    public function __construct(
        public TestDto $child,
        public string $title,
    ) {}
}

describe('Data DTO', function () {
    it('converts public properties to array', function () {
        $dto = new TestDto(name: 'test', count: 5, label: 'hello');

        expect($dto->toArray())->toBe([
            'name' => 'test', 'count' => 5, 'label' => 'hello',
        ]);
    });

    it('creates from array with camelCase keys', function () {
        $dto = TestDto::fromArray(['name' => 'from-array', 'count' => 10]);

        expect($dto->name)->toBe('from-array')
            ->and($dto->count)->toBe(10);
    });

    it('applies defaults for missing optional parameters', function () {
        $dto = TestDto::fromArray(['name' => 'defaults']);

        expect($dto->count)->toBe(0)
            ->and($dto->label)->toBeNull();
    });

    it('recursively converts nested DTOs', function () {
        $parent = new NestedDto(child: new TestDto(name: 'child'), title: 'parent');

        $result = $parent->toArray();

        expect($result)->toBe([
            'child' => ['name' => 'child', 'count' => 0, 'label' => null],
            'title' => 'parent',
        ]);
    });

    it('from() dispatches to fromArray for arrays', function () {
        $dto = TestDto::from(['name' => 'mixed', 'count' => 7]);

        expect($dto)->toBeInstanceOf(TestDto::class)
            ->and($dto->name)->toBe('mixed');
    });

    it('from() throws for unsupported source type', function () {
        TestDto::from('invalid');
    })->throws(InvalidArgumentException::class);

    it('fromArray throws when required parameter is missing', function () {
        TestDto::fromArray(['count' => 5]);
    })->throws(InvalidArgumentException::class, 'Missing required');
});
