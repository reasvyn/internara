<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Data\AcademicYearData;

test('academic year data can be created', function () {
    $data = new AcademicYearData(name: '2025/2026', startDate: '2025-07-01', endDate: '2026-06-30');

    expect($data->name)->toBe('2025/2026');
    expect($data->startDate)->toBe('2025-07-01');
    expect($data->endDate)->toBe('2026-06-30');
    expect($data->isActive)->toBeFalse();
});

test('academic year data can be created as active', function () {
    $data = new AcademicYearData(name: '2026/2027', startDate: '2026-07-01', endDate: '2027-06-30', isActive: true);

    expect($data->isActive)->toBeTrue();
});

test('academic year data from array', function () {
    $data = AcademicYearData::from(['name' => 'Test', 'startDate' => '2025-01-01', 'endDate' => '2025-12-31']);

    expect($data->name)->toBe('Test');
    expect($data->startDate)->toBe('2025-01-01');
});
