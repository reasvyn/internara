<?php

declare(strict_types=1);

use App\Program\InternshipGroup\Data\InternshipGroupData;

test('can be created with required fields', function () {
    $data = new InternshipGroupData(
        internshipId: 'int-1',
        name: 'Group A',
    );

    expect($data->internshipId)->toBe('int-1');
    expect($data->name)->toBe('Group A');
    expect($data->placementId)->toBeNull();
    expect($data->isActive)->toBeNull();
});

test('can include optional fields', function () {
    $data = new InternshipGroupData(
        internshipId: 'int-1',
        name: 'Group B',
        placementId: 'pl-1',
        isActive: true,
    );

    expect($data->placementId)->toBe('pl-1');
    expect($data->isActive)->toBeTrue();
});

test('is immutable', function () {
    $data = new InternshipGroupData(internshipId: 'i', name: 'G');

    $ref = new ReflectionClass($data);
    foreach ($ref->getProperties() as $property) {
        expect($property->isReadOnly())->toBeTrue();
    }
});

test('can be created from array', function () {
    $data = InternshipGroupData::from([
        'internship_id' => 'int-1',
        'name' => 'Group A',
    ]);

    expect($data)->toBeInstanceOf(InternshipGroupData::class);
    expect($data->internshipId)->toBe('int-1');
});
