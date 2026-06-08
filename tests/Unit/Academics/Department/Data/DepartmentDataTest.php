<?php

declare(strict_types=1);

use App\Academics\Department\Data\DepartmentData;

test('department data can be created with name only', function () {
    $data = new DepartmentData(name: 'RPL');

    expect($data->name)->toBe('RPL');
    expect($data->description)->toBeNull();
});

test('department data can be created with description', function () {
    $data = new DepartmentData(name: 'TKJ', description: 'Network Engineering');

    expect($data->description)->toBe('Network Engineering');
});

test('department data from array', function () {
    $data = DepartmentData::from(['name' => 'AKL', 'description' => 'Accounting']);

    expect($data->name)->toBe('AKL');
    expect($data->description)->toBe('Accounting');
});