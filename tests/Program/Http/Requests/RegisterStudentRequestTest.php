<?php

declare(strict_types=1);

use App\Program\Http\Requests\RegisterStudentRequest;
use Illuminate\Validation\Rules\Password;

test('authorizes all requests', function () {
    $request = new RegisterStudentRequest;

    expect($request->authorize())->toBeTrue();
});

test('has validation rules for all fields', function () {
    $request = new RegisterStudentRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'name',
        'email',
        'password',
        'student_id',
        'phone',
        'address',
    ]);
    expect($rules['name'])->toContain('required', 'string', 'max:255');
    expect($rules['email'])->toContain('required', 'email', 'max:255', 'unique:users,email');
    expect($rules['password'])->toContain('required', 'confirmed');
    expect($rules['student_id'])->toContain('required', 'string', 'max:50', 'unique:students,student_id');
    expect($rules['phone'])->toContain('sometimes', 'string', 'max:20');
    expect($rules['address'])->toContain('sometimes', 'string', 'max:500');
});

test('password rule uses defaults', function () {
    $request = new RegisterStudentRequest;

    $rules = $request->rules();

    $hasPasswordDefaults = false;
    foreach ($rules['password'] as $rule) {
        if ($rule instanceof Password) {
            $hasPasswordDefaults = true;
        }
    }
    expect($hasPasswordDefaults)->toBeTrue();
});

test('has custom error messages', function () {
    $request = new RegisterStudentRequest;

    $messages = $request->messages();

    expect($messages['email.unique'])->toBe('This email is already registered.');
    expect($messages['student_id.unique'])->toBe('This student ID is already registered.');
    expect($messages['password.confirmed'])->toBe('The password confirmation does not match.');
});
