<?php

declare(strict_types=1);

use App\Academics\School\Entities\SchoolEntity;

test('school entity returns all constructor values via getters', function () {
    $entity = new SchoolEntity(
        name: 'Test School',
        institutionalCode: 'SCH001',
        email: 'school@test.com',
        address: '123 Main St',
        phone: '555-0100',
        website: 'https://testschool.edu',
        principalName: 'Dr. Smith',
    );

    expect($entity->name())->toBe('Test School');
    expect($entity->institutionalCode())->toBe('SCH001');
    expect($entity->email())->toBe('school@test.com');
    expect($entity->address())->toBe('123 Main St');
    expect($entity->phone())->toBe('555-0100');
    expect($entity->website())->toBe('https://testschool.edu');
    expect($entity->principalName())->toBe('Dr. Smith');
});

test('school entity uses default empty strings for optional fields', function () {
    $entity = new SchoolEntity(
        name: 'Minimal School',
        institutionalCode: 'SCH002',
        email: 'minimal@test.com',
    );

    expect($entity->address())->toBe('');
    expect($entity->phone())->toBe('');
    expect($entity->website())->toBe('');
    expect($entity->principalName())->toBe('');
});
