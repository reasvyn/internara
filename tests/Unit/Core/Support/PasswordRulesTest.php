<?php

declare(strict_types=1);

use App\Core\Support\PasswordRules;

test('default returns expected rules', function () {
    $rules = PasswordRules::default();
    expect($rules)->toBeArray()->toHaveCount(3);
    expect($rules[0])->toBe('required');
    expect($rules[1])->toBe('string');
});

test('defaultAsArray returns expected rules', function () {
    $rules = PasswordRules::defaultAsArray();
    expect($rules)->toBeArray()
        ->toContain('required')
        ->toContain('string')
        ->toContain('min:8');
});

test('defaultAsArray contains uppercase regex', function () {
    expect(PasswordRules::defaultAsArray())->toContain('regex:/[A-Z]/');
});

test('defaultAsArray contains digit regex', function () {
    expect(PasswordRules::defaultAsArray())->toContain('regex:/[0-9]/');
});
