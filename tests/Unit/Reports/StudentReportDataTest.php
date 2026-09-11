<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\StudentReport\Data\CreateStudentReportData;

describe('R6BMW: CreateStudentReportData DTO', function (): void {
    test('R6BMW-FR-RPT-003: fromArray maps the registration identifier', function (): void {
        $dto = CreateStudentReportData::fromArray(['registrationId' => 'reg-1']);

        expect($dto->registrationId)->toBe('reg-1');
    });

    test('R6BMW-FR-RPT-003: fromArray accepts snake_case keys', function (): void {
        $dto = CreateStudentReportData::fromArray(['registration_id' => 'reg-2']);

        expect($dto->registrationId)->toBe('reg-2');
    });

    test('R6BMW-FR-RPT-003: fromArray throws when the registration is missing', function (): void {
        expect(fn (): CreateStudentReportData => CreateStudentReportData::fromArray([]))
            ->toThrow(InvalidArgumentException::class, 'registrationId');
    });

    test('R6BMW-FR-RPT-001: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(CreateStudentReportData::from(['registrationId' => 'reg-1'])->registrationId)->toBe('reg-1');

        $source = new class
        {
            public function toArray(): array
            {
                return ['registrationId' => 'reg-9'];
            }
        };

        expect(CreateStudentReportData::from($source)->registrationId)->toBe('reg-9');
        expect(fn (): CreateStudentReportData => CreateStudentReportData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('R6BMW-FR-RPT-003: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateStudentReportData(registrationId: 'reg-1');

        expect($dto->toArray())->toBe(['registrationId' => 'reg-1']);
        expect($dto->only('registrationId'))->toBe(['registrationId' => 'reg-1']);
        expect($dto->except('registrationId'))->toBe([]);

        $merged = $dto->merge(['registrationId' => 'reg-2']);

        expect($merged->registrationId)->toBe('reg-2');
        expect($dto->registrationId)->toBe('reg-1');
    });
});
