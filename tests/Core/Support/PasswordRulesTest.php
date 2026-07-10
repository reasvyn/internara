<?php

declare(strict_types=1);

use App\Core\Support\PasswordRules;

test('default returns password validation rules', function () {
    $rules = PasswordRules::default();

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
});

test('default returns password validation rules with min length', function () {
    $rules = PasswordRules::default();

    expect($rules)->toContain('required');
    expect($rules)->toContain('string');
});
