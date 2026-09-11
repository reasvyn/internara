<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\AccountApplication\Data\RejectAccountApplicationData;
use App\Modules\Enrollment\Domain\Registration\Data\RegistrationData;

describe('920SO: RejectAccountApplicationData DTO', function (): void {
    test('920SO-FR-APPLY-012: fromArray maps the application and the rejection reason', function (): void {
        $dto = RejectAccountApplicationData::fromArray([
            'applicationId' => 'app-1',
            'reason' => 'Data tidak lengkap.',
        ]);

        expect($dto->applicationId)->toBe('app-1');
        expect($dto->reason)->toBe('Data tidak lengkap.');
    });

    test('920SO-FR-APPLY-012: fromArray accepts snake_case keys', function (): void {
        $dto = RejectAccountApplicationData::fromArray(['application_id' => 'app-2', 'reason' => 'R']);

        expect($dto->applicationId)->toBe('app-2');
    });

    test('920SO-FR-APPLY-013: fromArray throws when the reason is missing', function (): void {
        expect(fn (): RejectAccountApplicationData => RejectAccountApplicationData::fromArray(['applicationId' => 'app-1']))
            ->toThrow(InvalidArgumentException::class, 'reason');
    });

    test('920SO-FR-APPLY-012: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['applicationId' => 'a', 'reason' => 'r'];

        expect(RejectAccountApplicationData::from($payload)->applicationId)->toBe('a');

        $source = new class
        {
            public function toArray(): array
            {
                return ['applicationId' => 'a2', 'reason' => 'r2'];
            }
        };

        expect(RejectAccountApplicationData::from($source)->reason)->toBe('r2');
        expect(fn (): RejectAccountApplicationData => RejectAccountApplicationData::from([]))->toThrow(InvalidArgumentException::class);
    });

    test('920SO-FR-APPLY-012: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new RejectAccountApplicationData(applicationId: 'a', reason: 'r');

        expect($dto->toArray())->toBe(['applicationId' => 'a', 'reason' => 'r']);
        expect($dto->only('reason'))->toBe(['reason' => 'r']);
        expect($dto->except('reason'))->toBe(['applicationId' => 'a']);

        $merged = $dto->merge(['reason' => 'r-baru']);

        expect($merged->reason)->toBe('r-baru');
        expect($dto->reason)->toBe('r');
    });
});

describe('MBB5R: RegistrationData DTO', function (): void {
    test('MBB5R-FR-REG-031: fromArray requires only the internship reference', function (): void {
        $dto = RegistrationData::fromArray(['internshipId' => 'intern-1']);

        expect($dto->internshipId)->toBe('intern-1');
        expect($dto->placementId)->toBeNull();
        expect($dto->academicYear)->toBeNull();
        expect($dto->startDate)->toBeNull();
        expect($dto->endDate)->toBeNull();
        expect($dto->proposedCompanyName)->toBeNull();
        expect($dto->proposedCompanyAddress)->toBeNull();
    });

    test('MBB5R-FR-REG-032: fromArray accepts snake_case keys for every optional field', function (): void {
        $dto = RegistrationData::fromArray([
            'internship_id' => 'intern-2',
            'placement_id' => 'place-1',
            'academic_year' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'proposed_company_name' => 'PT Maju',
            'proposed_company_address' => 'Jl. Merdeka 1',
        ]);

        expect($dto->internshipId)->toBe('intern-2');
        expect($dto->placementId)->toBe('place-1');
        expect($dto->academicYear)->toBe('2026/2027');
        expect($dto->startDate)->toBe('2026-07-01');
        expect($dto->endDate)->toBe('2026-12-31');
        expect($dto->proposedCompanyName)->toBe('PT Maju');
        expect($dto->proposedCompanyAddress)->toBe('Jl. Merdeka 1');
    });

    test('MBB5R-FR-REG-031: fromArray throws when the internship is missing', function (): void {
        expect(fn (): RegistrationData => RegistrationData::fromArray(['placementId' => 'place-1']))
            ->toThrow(InvalidArgumentException::class, 'internshipId');
    });

    test('MBB5R-FR-REG-031: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(RegistrationData::from(['internshipId' => 'i'])->internshipId)->toBe('i');

        $source = new class
        {
            public function toArray(): array
            {
                return ['internshipId' => 'i2', 'placementId' => 'p2'];
            }
        };

        expect(RegistrationData::from($source)->placementId)->toBe('p2');
        expect(fn (): RegistrationData => RegistrationData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('MBB5R-FR-REG-032: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new RegistrationData(internshipId: 'i');

        expect($dto->toArray())->toBe([
            'internshipId' => 'i',
            'placementId' => null,
            'academicYear' => null,
            'startDate' => null,
            'endDate' => null,
            'proposedCompanyName' => null,
            'proposedCompanyAddress' => null,
        ]);
        expect($dto->only('internshipId'))->toBe(['internshipId' => 'i']);
        expect(array_key_exists('placementId', $dto->except('internshipId')))->toBeTrue();

        $merged = $dto->merge(['placementId' => 'place-9']);

        expect($merged->placementId)->toBe('place-9');
        expect($dto->placementId)->toBeNull();
    });
});
