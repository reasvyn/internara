<?php

declare(strict_types=1);

use App\Modules\Program\Domain\InternshipGroup\Data\InternshipGroupData;

describe('IT0OE: InternshipGroupData DTO', function (): void {
    test('IT0OE-FR-GROUP-012: fromArray maps internship and name with null optionals', function (): void {
        $dto = InternshipGroupData::fromArray(['internshipId' => 'intern-1', 'name' => 'Kelompok A']);

        expect($dto->internshipId)->toBe('intern-1');
        expect($dto->name)->toBe('Kelompok A');
        expect($dto->placementId)->toBeNull();
        expect($dto->isActive)->toBeNull();
    });

    test('IT0OE-FR-GROUP-012: fromArray accepts snake_case keys', function (): void {
        $dto = InternshipGroupData::fromArray([
            'internship_id' => 'intern-2',
            'name' => 'Kelompok B',
            'placement_id' => 'place-1',
            'is_active' => true,
        ]);

        expect($dto->internshipId)->toBe('intern-2');
        expect($dto->placementId)->toBe('place-1');
        expect($dto->isActive)->toBeTrue();
    });

    test('IT0OE-FR-GROUP-007: fromArray throws when the internship is missing', function (): void {
        expect(fn (): InternshipGroupData => InternshipGroupData::fromArray(['name' => 'Kelompok A']))
            ->toThrow(InvalidArgumentException::class, 'internshipId');
    });

    test('IT0OE-FR-GROUP-007: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(InternshipGroupData::from(['internshipId' => 'i', 'name' => 'G'])->name)->toBe('G');

        $source = new class
        {
            public function toArray(): array
            {
                return ['internshipId' => 'i2', 'name' => 'G2', 'isActive' => false];
            }
        };

        expect(InternshipGroupData::from($source)->isActive)->toBeFalse();
        expect(fn (): InternshipGroupData => InternshipGroupData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('IT0OE-FR-GROUP-012: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new InternshipGroupData(internshipId: 'i', name: 'G');

        expect($dto->toArray())->toBe(['internshipId' => 'i', 'name' => 'G', 'placementId' => null, 'isActive' => null]);
        expect($dto->only('name'))->toBe(['name' => 'G']);
        expect($dto->except('placementId', 'isActive'))->toBe(['internshipId' => 'i', 'name' => 'G']);

        $merged = $dto->merge(['isActive' => true]);

        expect($merged->isActive)->toBeTrue();
        expect($dto->isActive)->toBeNull();
    });
});
