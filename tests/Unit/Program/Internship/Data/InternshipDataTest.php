<?php

declare(strict_types=1);

use App\Program\Internship\Data\InternshipData;

test('internship data can be created with required fields', function () {
    $data = new InternshipData(
        name: 'PKL 2025',
        academicYearId: 'ay-1',
        startDate: '2025-07-01',
        endDate: '2025-12-31',
    );

    expect($data->name)->toBe('PKL 2025');
});

test('internship data can include registration window', function () {
    $data = new InternshipData(
        name: 'PKL 2025',
        academicYearId: 'ay-1',
        startDate: '2025-07-01',
        endDate: '2025-12-31',
        registrationStartDate: '2025-01-01',
        registrationEndDate: '2025-06-30',
    );

    expect($data->registrationStartDate)->toBe('2025-01-01');
});

test('internship data is immutable', function () {
    $data = new InternshipData(name: 'N', academicYearId: 'a', startDate: 's', endDate: 'e');

    $r = new ReflectionClass($data);
    foreach ($r->getProperties() as $p) {
        expect($p->isReadOnly())->toBeTrue();
    }
});
