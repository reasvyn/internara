<?php

declare(strict_types=1);

use App\Program\Http\Requests\CreateInternshipRequest;
use Illuminate\Validation\Rules\Enum;

test('has validation rules for all fields', function () {
    $request = new CreateInternshipRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'company_id',
        'department_id',
    ]);
    expect($rules['name'])->toContain('required', 'string', 'max:255');
    expect($rules['description'])->toContain('nullable', 'string', 'max:5000');
    expect($rules['start_date'])->toContain('required', 'date', 'after:today');
    expect($rules['end_date'])->toContain('required', 'date', 'after:start_date');
    expect($rules['company_id'])->toContain('required', 'uuid', 'exists:companies,id');
    expect($rules['department_id'])->toContain('required', 'uuid', 'exists:departments,id');
});

test('status rule is optional and uses enum', function () {
    $request = new CreateInternshipRequest;

    $rules = $request->rules();

    expect($rules['status'])->toContain('sometimes', 'string');

    $hasEnum = false;
    foreach ($rules['status'] as $rule) {
        if ($rule instanceof Enum) {
            $hasEnum = true;
        }
    }
    expect($hasEnum)->toBeTrue();
});

test('has custom error messages', function () {
    $request = new CreateInternshipRequest;

    $messages = $request->messages();

    expect($messages['start_date.after'])->toBe('The start date must be in the future.');
    expect($messages['end_date.after'])->toBe('The end date must be after the start date.');
    expect($messages['company_id.exists'])->toBe('The selected company does not exist.');
    expect($messages['department_id.exists'])->toBe('The selected department does not exist.');
});

test('has custom attribute names', function () {
    $request = new CreateInternshipRequest;

    $attributes = $request->attributes();

    expect($attributes['company_id'])->toBe('company');
    expect($attributes['department_id'])->toBe('department');
});
