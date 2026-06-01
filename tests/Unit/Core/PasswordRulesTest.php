<?php

declare(strict_types=1);

use App\Domain\Core\Support\PasswordRules;

describe('PasswordRules', function () {
    it('default() returns array with required and string', function () {
        $rules = PasswordRules::default();

        expect($rules)->toContain('required', 'string');
    });

    it('defaultAsArray() returns explicit min:8 with regex rules', function () {
        $rules = PasswordRules::defaultAsArray();

        expect($rules)->toContain('required', 'string', 'min:8')
            ->toContain('regex:/[A-Z]/')
            ->toContain('regex:/[a-z]/')
            ->toContain('regex:/[0-9]/');
    });
});
