<?php

declare(strict_types=1);

use App\Partners\Partnership\Data\PartnershipData;

test('partnership data can be created with required fields', function () {
    $data = new PartnershipData(
        companyId: 'company-1',
        agreementNumber: 'MOU-001',
        title: 'Test Agreement',
        startDate: '2026-01-01',
        endDate: '2026-12-31',
    );

    expect($data->companyId)->toBe('company-1');
    expect($data->agreementNumber)->toBe('MOU-001');
    expect($data->title)->toBe('Test Agreement');
    expect($data->startDate)->toBe('2026-01-01');
    expect($data->endDate)->toBe('2026-12-31');
    expect($data->scope)->toBeNull();
});

test('partnership data can be created with all fields', function () {
    $data = new PartnershipData(
        companyId: 'company-1',
        agreementNumber: 'MOU-001',
        title: 'Test',
        startDate: '2026-01-01',
        endDate: '2026-12-31',
        scope: 'Full scope',
        contactPersonName: 'John Doe',
        contactPersonPhone: '021-1234',
        contactPersonEmail: 'john@example.com',
        signedBySchool: 'Principal',
        signedByCompany: 'CEO',
        signedAt: '2026-01-15',
        notes: 'Some notes',
    );

    expect($data->scope)->toBe('Full scope');
    expect($data->contactPersonName)->toBe('John Doe');
});

test('partnership data is immutable', function () {
    $data = new PartnershipData(
        companyId: 'c1',
        agreementNumber: 'MOU-001',
        title: 'Test',
        startDate: '2026-01-01',
        endDate: '2026-12-31',
    );

    $r = new ReflectionClass($data);
    foreach ($r->getProperties() as $p) {
        expect($p->isReadOnly())->toBeTrue();
    }
});

test('partnership data fromArray handles snake_case keys', function () {
    $data = PartnershipData::fromArray([
        'company_id' => 'c1',
        'agreement_number' => 'MOU-001',
        'title' => 'Test',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    expect($data->companyId)->toBe('c1');
    expect($data->agreementNumber)->toBe('MOU-001');
});
