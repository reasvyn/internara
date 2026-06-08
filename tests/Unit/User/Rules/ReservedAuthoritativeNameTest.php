<?php

declare(strict_types=1);

use App\User\Rules\ReservedAuthoritativeName;

test('passes for non-reserved names', function () {
    $rule = new ReservedAuthoritativeName;
    $passed = true;
    $rule->validate('name', 'John Doe', function () use (&$passed) { $passed = false; });

    expect($passed)->toBeTrue();
});

test('fails for reserved name administrator', function () {
    $rule = new ReservedAuthoritativeName;
    $failed = false;
    $rule->validate('name', 'administrator', function () use (&$failed) { $failed = true; });

    expect($failed)->toBeTrue();
});

test('fails for reserved name superadmin', function () {
    $rule = new ReservedAuthoritativeName;
    $failed = false;
    $rule->validate('name', 'superadmin', function () use (&$failed) { $failed = true; });

    expect($failed)->toBeTrue();
});

test('fails for reserved name root', function () {
    $rule = new ReservedAuthoritativeName;
    $failed = false;
    $rule->validate('name', 'root', function () use (&$failed) { $failed = true; });

    expect($failed)->toBeTrue();
});

test('is case insensitive', function () {
    $rule = new ReservedAuthoritativeName;
    $failed = false;
    $rule->validate('name', 'Admin', function () use (&$failed) { $failed = true; });

    expect($failed)->toBeTrue();
});

test('passes for null value', function () {
    $rule = new ReservedAuthoritativeName;
    $passed = true;
    $rule->validate('name', null, function () use (&$passed) { $passed = false; });

    expect($passed)->toBeTrue();
});