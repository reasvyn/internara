<?php

declare(strict_types=1);

use App\Support\PasswordRules;

test('default returns password validation rules', function () {
    $rules = PasswordRules::default();

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
});

test('defaultAsArray returns array rules with regex', function () {
    $rules = PasswordRules::defaultAsArray();

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
    expect($rules)->toContain('min:8');
    expect($rules)->toContain('regex:/[A-Z]/');
    expect($rules)->toContain('regex:/[a-z]/');
    expect($rules)->toContain('regex:/[0-9]/');
});
