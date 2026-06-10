<?php

declare(strict_types=1);

use App\Enrollment\Registration\Data\RegistrationData;

test('registration data can be created with required fields', function () {
    $data = new RegistrationData(internshipId: 'i-1');

    expect($data->internshipId)->toBe('i-1');
    expect($data->placementId)->toBeNull();
});

test('registration data can be created with all fields', function () {
    $data = new RegistrationData(
        internshipId: 'i-1',
        placementId: 'p-1',
        academicYear: '2025/2026',
        startDate: '2025-07-01',
        endDate: '2025-12-31',
        proposedCompanyName: 'PT Maju',
        proposedCompanyAddress: 'Jakarta',
    );

    expect($data->placementId)->toBe('p-1');
    expect($data->academicYear)->toBe('2025/2026');
});

test('registration data is immutable', function () {
    $data = new RegistrationData(internshipId: 'i-1');

    $r = new ReflectionClass($data);
    foreach ($r->getProperties() as $p) {
        expect($p->isReadOnly())->toBeTrue();
    }
});

test('registration data from array', function () {
    $data = RegistrationData::from(['internshipId' => 'i-1', 'placementId' => 'p-1']);

    expect($data->internshipId)->toBe('i-1');
    expect($data->placementId)->toBe('p-1');
});
