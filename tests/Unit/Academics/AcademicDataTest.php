<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\AcademicYear\Data\AcademicYearData;
use App\Modules\Academics\Domain\Department\Data\DepartmentData;

describe('XW6F5: AcademicYearData DTO', function (): void {
    test('XW6F5-FR-YEAR-001: fromArray maps exact keys with inactive default', function (): void {
        $dto = AcademicYearData::fromArray(['name' => '2026/2027']);

        expect($dto->name)->toBe('2026/2027');
        expect($dto->startDate)->toBeNull();
        expect($dto->endDate)->toBeNull();
        expect($dto->isActive)->toBeFalse();
        expect($dto->id)->toBeNull();
    });

    test('XW6F5-FR-YEAR-001: fromArray accepts snake_case keys', function (): void {
        $dto = AcademicYearData::fromArray([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        expect($dto->startDate)->toBe('2026-07-01');
        expect($dto->endDate)->toBe('2027-06-30');
        expect($dto->isActive)->toBeTrue();
    });

    test('XW6F5-FR-YEAR-004: fromArray throws when the name is missing', function (): void {
        expect(fn (): AcademicYearData => AcademicYearData::fromArray(['is_active' => true]))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('XW6F5-FR-YEAR-004: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(AcademicYearData::from(['name' => '2026/2027'])->name)->toBe('2026/2027');

        $source = new class
        {
            public function toArray(): array
            {
                return ['name' => '2025/2026', 'is_active' => true];
            }
        };

        $dto = AcademicYearData::from($source);

        expect($dto->name)->toBe('2025/2026');
        expect($dto->isActive)->toBeTrue();
        expect(fn (): AcademicYearData => AcademicYearData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('XW6F5-FR-YEAR-005: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new AcademicYearData(name: '2026/2027', startDate: '2026-07-01', endDate: '2027-06-30');

        expect($dto->toArray())->toBe([
            'name' => '2026/2027',
            'startDate' => '2026-07-01',
            'endDate' => '2027-06-30',
            'isActive' => false,
            'id' => null,
        ]);
        expect($dto->only('name'))->toBe(['name' => '2026/2027']);
        expect($dto->except('id', 'isActive'))->toBe([
            'name' => '2026/2027',
            'startDate' => '2026-07-01',
            'endDate' => '2027-06-30',
        ]);

        $merged = $dto->merge(['isActive' => true]);

        expect($merged->isActive)->toBeTrue();
        expect($dto->isActive)->toBeFalse();
    });
});

describe('4HWSB: DepartmentData DTO', function (): void {
    test('4HWSB-FR-DEPT-010: fromArray maps exact keys with optional id', function (): void {
        $dto = DepartmentData::fromArray(['name' => 'RPL', 'description' => 'Rekayasa Perangkat Lunak']);

        expect($dto->name)->toBe('RPL');
        expect($dto->description)->toBe('Rekayasa Perangkat Lunak');
        expect($dto->id)->toBeNull();
    });

    test('4HWSB-FR-DEPT-010: fromArray throws when the name is missing', function (): void {
        expect(fn (): DepartmentData => DepartmentData::fromArray(['description' => 'x']))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('4HWSB-FR-DEPT-010: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(DepartmentData::from(['name' => 'TKJ'])->name)->toBe('TKJ');

        $source = new class
        {
            public function toArray(): array
            {
                return ['name' => 'MM', 'id' => 'dept-1'];
            }
        };

        expect(DepartmentData::from($source)->id)->toBe('dept-1');
        expect(fn (): DepartmentData => DepartmentData::from('nope'))->toThrow(InvalidArgumentException::class);
    });

    test('4HWSB-FR-DEPT-010: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new DepartmentData(name: 'RPL');

        expect($dto->toArray())->toBe(['name' => 'RPL', 'description' => null, 'id' => null]);
        expect($dto->only('name'))->toBe(['name' => 'RPL']);
        expect($dto->except('id'))->toBe(['name' => 'RPL', 'description' => null]);

        $merged = $dto->merge(['description' => 'Software']);

        expect($merged->description)->toBe('Software');
        expect($dto->description)->toBeNull();
    });
});
