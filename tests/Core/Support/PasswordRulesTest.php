<?php

declare(strict_types=1);

use App\Core\Support\PasswordRules;
use Illuminate\Validation\Rules\Password;

it('FR-SUP3: default() enforces required, string, and a strong password rule', function () {
    $rules = PasswordRules::default();

    expect($rules)->toHaveCount(3);
    expect($rules[0])->toBe('required');
    expect($rules[1])->toBe('string');
    expect($rules[2])->toBeInstanceOf(Password::class);
});
