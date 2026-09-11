<?php

declare(strict_types=1);

use App\Modules\Core\Support\PasswordRules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

describe('C8F0D: password rules', function (): void {
    test('C8F0D-FR-UTIL-005: default composition requires length, mixed case, and numbers', function (): void {
        $rules = PasswordRules::default();

        expect($rules[0])->toBe('required');
        expect($rules[1])->toBe('string');
        expect($rules[2])->toBeInstanceOf(Password::class);
    });

    test('C8F0D-FR-UTIL-005: a strong password passes, weak shapes fail', function (): void {
        $passes = fn (string $password): bool => Validator::make(
            ['password' => $password],
            ['password' => PasswordRules::default()],
        )->passes();

        expect($passes('StrongPass1'))->toBeTrue();
        expect($passes('short1A'))->toBeFalse();
        expect($passes('alllowercase1'))->toBeFalse();
        expect($passes('ALLUPPERCASE1'))->toBeFalse();
        expect($passes('NoNumbersHere'))->toBeFalse();
    });

    test('C8F0D-FR-UTIL-005: the minimum length is configurable', function (): void {
        $passes = fn (string $password): bool => Validator::make(
            ['password' => $password],
            ['password' => PasswordRules::default(12)],
        )->passes();

        expect($passes('StrongPass1'))->toBeFalse();
        expect($passes('StrongPass12'))->toBeTrue();
    });
});
