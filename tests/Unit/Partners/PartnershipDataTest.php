<?php

declare(strict_types=1);

use App\Modules\Partners\Domain\Partnership\Data\PartnershipData;

describe('NTHQA: PartnershipData DTO', function (): void {
    test('NTHQA-FR-PART-007: fromArray maps the agreement core with null optionals', function (): void {
        $dto = PartnershipData::fromArray([
            'companyId' => 'comp-1',
            'agreementNumber' => 'MOU/2026/001',
            'title' => 'Kerja sama PKL',
            'startDate' => '2026-07-01',
            'endDate' => '2027-06-30',
        ]);

        expect($dto->companyId)->toBe('comp-1');
        expect($dto->agreementNumber)->toBe('MOU/2026/001');
        expect($dto->title)->toBe('Kerja sama PKL');
        expect($dto->startDate)->toBe('2026-07-01');
        expect($dto->endDate)->toBe('2027-06-30');
        expect($dto->scope)->toBeNull();
        expect($dto->notes)->toBeNull();
    });

    test('NTHQA-FR-PART-007: fromArray accepts snake_case keys with contacts and signatories', function (): void {
        $dto = PartnershipData::fromArray([
            'company_id' => 'comp-2',
            'agreement_number' => 'MOU/2026/002',
            'title' => 'T',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'contact_person_name' => 'Budi',
            'contact_person_phone' => '0812',
            'contact_person_email' => 'budi@pt.co.id',
            'signed_by_school' => 'Kepala Sekolah',
            'signed_by_company' => 'Direktur',
            'signed_at' => '2026-07-02',
        ]);

        expect($dto->companyId)->toBe('comp-2');
        expect($dto->contactPersonName)->toBe('Budi');
        expect($dto->contactPersonPhone)->toBe('0812');
        expect($dto->contactPersonEmail)->toBe('budi@pt.co.id');
        expect($dto->signedBySchool)->toBe('Kepala Sekolah');
        expect($dto->signedByCompany)->toBe('Direktur');
        expect($dto->signedAt)->toBe('2026-07-02');
    });

    test('NTHQA-FR-PART-007: fromArray throws when the agreement number is missing', function (): void {
        expect(fn (): PartnershipData => PartnershipData::fromArray([
            'companyId' => 'comp-1',
            'title' => 'T',
            'startDate' => '2026-07-01',
            'endDate' => '2027-06-30',
        ]))->toThrow(InvalidArgumentException::class, 'agreementNumber');
    });

    test('NTHQA-FR-PART-007: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = [
            'companyId' => 'c',
            'agreementNumber' => 'MOU/1',
            'title' => 'T',
            'startDate' => '2026-07-01',
            'endDate' => '2027-06-30',
        ];

        expect(PartnershipData::from($payload)->agreementNumber)->toBe('MOU/1');

        $source = new class
        {
            public function toArray(): array
            {
                return [
                    'companyId' => 'c2',
                    'agreementNumber' => 'MOU/2',
                    'title' => 'T2',
                    'startDate' => '2026-07-01',
                    'endDate' => '2027-06-30',
                    'scope' => 'Nasional',
                ];
            }
        };

        expect(PartnershipData::from($source)->scope)->toBe('Nasional');
        expect(fn (): PartnershipData => PartnershipData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('NTHQA-FR-PART-007: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new PartnershipData(
            companyId: 'c',
            agreementNumber: 'MOU/1',
            title: 'T',
            startDate: '2026-07-01',
            endDate: '2027-06-30',
        );

        expect($dto->toArray()['agreementNumber'])->toBe('MOU/1');
        expect($dto->only('agreementNumber', 'title'))->toBe(['agreementNumber' => 'MOU/1', 'title' => 'T']);
        expect(array_key_exists('notes', $dto->except('agreementNumber')))->toBeTrue();

        $merged = $dto->merge(['scope' => 'Regional']);

        expect($merged->scope)->toBe('Regional');
        expect($dto->scope)->toBeNull();
    });
});
