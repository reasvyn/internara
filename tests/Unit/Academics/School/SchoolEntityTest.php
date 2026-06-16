<?php

declare(strict_types=1);

use App\Academics\School\Entities\SchoolEntity;
use App\Settings\Support\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('SchoolEntity get method returns populated entity from settings', function () {
    Settings::set([
        'school.name' => ['value' => 'SMK Negeri 1 Test', 'group' => 'school', 'type' => 'string'],
        'school.institutional_code' => [
            'value' => '12345678',
            'group' => 'school',
            'type' => 'string',
        ],
        'school.email' => [
            'value' => 'school@test.sch.id',
            'group' => 'school',
            'type' => 'string',
        ],
        'school.address' => ['value' => 'Jl. Test No. 1', 'group' => 'school', 'type' => 'string'],
        'school.phone' => ['value' => '021-123456', 'group' => 'school', 'type' => 'string'],
        'school.website' => [
            'value' => 'https://test.sch.id',
            'group' => 'school',
            'type' => 'string',
        ],
        'school.principal_name' => [
            'value' => 'Dr. Principal',
            'group' => 'school',
            'type' => 'string',
        ],
    ]);

    $school = SchoolEntity::get();

    expect($school)->toBeInstanceOf(SchoolEntity::class);
    expect($school->name())->toBe('SMK Negeri 1 Test');
    expect($school->institutionalCode())->toBe('12345678');
    expect($school->email())->toBe('school@test.sch.id');
    expect($school->address())->toBe('Jl. Test No. 1');
    expect($school->phone())->toBe('021-123456');
    expect($school->website())->toBe('https://test.sch.id');
    expect($school->principalName())->toBe('Dr. Principal');
});

test('SchoolEntity returns empty strings when settings are not present', function () {
    $school = SchoolEntity::get();

    expect($school->name())->toBe('');
    expect($school->institutionalCode())->toBe('');
    expect($school->email())->toBe('');
    expect($school->address())->toBe('');
    expect($school->phone())->toBe('');
    expect($school->website())->toBe('');
    expect($school->principalName())->toBe('');
});
