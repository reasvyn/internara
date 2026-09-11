<?php

declare(strict_types=1);

use App\Modules\Program\Domain\Internship\Data\InternshipData;

describe('7C5WM: InternshipData DTO', function (): void {
    test('7C5WM-FR-LIFE-005: fromArray maps required fields with null optionals', function (): void {
        $dto = InternshipData::fromArray([
            'name' => 'PKL 2026',
            'academicYearId' => 'year-1',
            'startDate' => '2026-07-01',
            'endDate' => '2026-12-31',
        ]);

        expect($dto->name)->toBe('PKL 2026');
        expect($dto->academicYearId)->toBe('year-1');
        expect($dto->startDate)->toBe('2026-07-01');
        expect($dto->endDate)->toBe('2026-12-31');
        expect($dto->description)->toBeNull();
        expect($dto->status)->toBeNull();
        expect($dto->registrationStartDate)->toBeNull();
        expect($dto->registrationEndDate)->toBeNull();
    });

    test('7C5WM-FR-LIFE-005: fromArray accepts snake_case keys with the registration window', function (): void {
        $dto = InternshipData::fromArray([
            'name' => 'PKL 2027',
            'academic_year_id' => 'year-2',
            'start_date' => '2027-07-01',
            'end_date' => '2027-12-31',
            'registration_start_date' => '2027-06-01',
            'registration_end_date' => '2027-06-30',
            'status' => 'draft',
        ]);

        expect($dto->academicYearId)->toBe('year-2');
        expect($dto->registrationStartDate)->toBe('2027-06-01');
        expect($dto->registrationEndDate)->toBe('2027-06-30');
        expect($dto->status)->toBe('draft');
    });

    test('7C5WM-FR-LIFE-005: fromArray throws when the academic year is missing', function (): void {
        expect(fn (): InternshipData => InternshipData::fromArray([
            'name' => 'PKL 2026',
            'startDate' => '2026-07-01',
            'endDate' => '2026-12-31',
        ]))->toThrow(InvalidArgumentException::class, 'academicYearId');
    });

    test('7C5WM-FR-LIFE-006: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['name' => 'P', 'academicYearId' => 'y', 'startDate' => '2026-07-01', 'endDate' => '2026-12-31'];

        expect(InternshipData::from($payload)->name)->toBe('P');

        $source = new class
        {
            public function toArray(): array
            {
                return [
                    'name' => 'P2',
                    'academicYearId' => 'y2',
                    'startDate' => '2026-07-01',
                    'endDate' => '2026-12-31',
                    'description' => 'Gelombang 2',
                ];
            }
        };

        expect(InternshipData::from($source)->description)->toBe('Gelombang 2');
        expect(fn (): InternshipData => InternshipData::from(null))->toThrow(InvalidArgumentException::class);
    });

    test('7C5WM-FR-LIFE-005: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new InternshipData(name: 'P', academicYearId: 'y', startDate: '2026-07-01', endDate: '2026-12-31');

        expect($dto->toArray())->toBe([
            'name' => 'P',
            'academicYearId' => 'y',
            'startDate' => '2026-07-01',
            'endDate' => '2026-12-31',
            'description' => null,
            'status' => null,
            'registrationStartDate' => null,
            'registrationEndDate' => null,
        ]);
        expect($dto->only('name', 'status'))->toBe(['name' => 'P', 'status' => null]);
        expect(array_key_exists('description', $dto->except('name')))->toBeTrue();

        $merged = $dto->merge(['status' => 'published']);

        expect($merged->status)->toBe('published');
        expect($dto->status)->toBeNull();
    });
});
