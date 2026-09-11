<?php

declare(strict_types=1);

use App\Modules\Certification\Data\BatchIssueCertificatesData;

describe('J0M04: BatchIssueCertificatesData DTO', function (): void {
    test('J0M04-FR-CERT-012: fromArray maps registrations, status, and template', function (): void {
        $dto = BatchIssueCertificatesData::fromArray([
            'registrationIds' => ['reg-1', 'reg-2'],
            'status' => 'issued',
            'templateId' => 'tpl-1',
        ]);

        expect($dto->registrationIds)->toBe(['reg-1', 'reg-2']);
        expect($dto->status)->toBe('issued');
        expect($dto->templateId)->toBe('tpl-1');
    });

    test('J0M04-FR-CERT-012: fromArray accepts snake_case keys', function (): void {
        $dto = BatchIssueCertificatesData::fromArray([
            'registration_ids' => ['reg-9'],
            'status' => 'issued',
            'template_id' => 'tpl-9',
        ]);

        expect($dto->registrationIds)->toBe(['reg-9']);
        expect($dto->templateId)->toBe('tpl-9');
    });

    test('J0M04-FR-CERT-012: fromArray throws when the template is missing', function (): void {
        expect(fn (): BatchIssueCertificatesData => BatchIssueCertificatesData::fromArray([
            'registrationIds' => ['reg-1'],
            'status' => 'issued',
        ]))->toThrow(InvalidArgumentException::class, 'templateId');
    });

    test('J0M04-FR-CERT-012: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['registrationIds' => [], 'status' => 'issued', 'templateId' => 'tpl'];

        expect(BatchIssueCertificatesData::from($payload)->registrationIds)->toBe([]);

        $source = new class
        {
            public function toArray(): array
            {
                return ['registrationIds' => ['r'], 'status' => 'issued', 'templateId' => 'tpl-2'];
            }
        };

        expect(BatchIssueCertificatesData::from($source)->templateId)->toBe('tpl-2');
        expect(fn (): BatchIssueCertificatesData => BatchIssueCertificatesData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('J0M04-FR-CERT-013: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new BatchIssueCertificatesData(registrationIds: ['reg-1'], status: 'issued', templateId: 'tpl-1');

        expect($dto->toArray())->toBe(['registrationIds' => ['reg-1'], 'status' => 'issued', 'templateId' => 'tpl-1']);
        expect($dto->only('status'))->toBe(['status' => 'issued']);
        expect($dto->except('status'))->toBe(['registrationIds' => ['reg-1'], 'templateId' => 'tpl-1']);

        $merged = $dto->merge(['registrationIds' => ['reg-1', 'reg-2']]);

        expect($merged->registrationIds)->toBe(['reg-1', 'reg-2']);
        expect($dto->registrationIds)->toBe(['reg-1']);
    });
});
