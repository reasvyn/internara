<?php

declare(strict_types=1);

use App\Domain\Core\Support\PasswordRules;

describe('PasswordRules', function () {
    it('returns default rule set', function () {
        $rules = PasswordRules::default();

        expect($rules)->toBeArray()
            ->and($rules)->toContain('required', 'string');
    });

    it('returns array-based rules', function () {
        $rules = PasswordRules::defaultAsArray();

        expect($rules)->toBeArray()
            ->and($rules)->toContain('required', 'string', 'min:8');
    });

    it('requires mixed case and numbers in array rules', function () {
        $rules = PasswordRules::defaultAsArray();

        expect($rules)->toContain('regex:/[A-Z]/')
            ->toContain('regex:/[a-z]/')
            ->toContain('regex:/[0-9]/');
    });
});
