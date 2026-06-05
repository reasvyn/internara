<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Academics\School\Models\School;
use App\Setup\Actions\SetupSchoolAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('setup school action successfully creates school and logs it', function () {
    $action = new SetupSchoolAction;

    $data = [
        'name' => 'State Vocational School 1',
        'institutional_code' => 'SCH-1002',
        'email' => 'smk1@sch.id',
        'address' => '123 Education St',
        'phone' => '021-5551234',
        'website' => 'https://smk1.sch.id',
        'principal_name' => 'Dr. John Doe',
    ];

    $school = $action->execute($data);

    expect($school)->toBeInstanceOf(School::class);
    expect($school->name)->toBe('State Vocational School 1');
    expect($school->institutional_code)->toBe('SCH-1002');
    expect(School::count())->toBe(1);
});

test('setup school action throws validation exception on invalid website', function () {
    $action = new SetupSchoolAction;

    $data = [
        'name' => 'State Vocational School 1',
        'institutional_code' => 'SCH-1002',
        'email' => 'smk1@sch.id',
        'website' => 'not-a-valid-url',
    ];

    expect(fn () => $action->execute($data))->toThrow(ValidationException::class);
});
