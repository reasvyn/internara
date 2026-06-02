<?php

declare(strict_types=1);

use App\Domain\Core\Support\PasswordRules;
use Illuminate\Validation\Rules\Password;

describe('PasswordRules', function () {
    it('returns default rules as an array', function () {
        $rules = PasswordRules::default();

        expect($rules)->toBeArray()->not->toBeEmpty()
            ->and($rules[0])->toBe('required')
            ->and($rules[1])->toBe('string');
    });

    it('returns default rules as plain array', function () {
        $rules = PasswordRules::defaultAsArray();

        expect($rules)->toBe(['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/']);
    });

    it('default rules enforce minimum length', function () {
        $rules = PasswordRules::default();

        $hasMin = collect($rules)->contains(fn ($rule) => $rule instanceof Password);

        expect($hasMin)->toBeTrue();
    });
});
