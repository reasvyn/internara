<?php

declare(strict_types=1);

use App\User\Rules\SystemUsername;

test('passes for valid usernames', function () {
    $rule = new SystemUsername;
    $passed = true;
    $rule->validate('username', 'johndoe', function () use (&$passed) { $passed = false; });
    expect($passed)->toBeTrue();

    $rule->validate('username', 'john123', function () use (&$passed) { $passed = false; });
    expect($passed)->toBeTrue();

    $rule->validate('username', 'abc', function () use (&$passed) { $passed = false; });
    expect($passed)->toBeTrue();
});

test('fails for uppercase letters', function () {
    $rule = new SystemUsername;
    $failed = false;
    $rule->validate('username', 'JohnDoe', function () use (&$failed) { $failed = true; });
    expect($failed)->toBeTrue();
});

test('fails for usernames starting with number', function () {
    $rule = new SystemUsername;
    $failed = false;
    $rule->validate('username', '1johndoe', function () use (&$failed) { $failed = true; });
    expect($failed)->toBeTrue();
});

test('fails for usernames with special characters', function () {
    $rule = new SystemUsername;
    $failed = false;
    $rule->validate('username', 'john.doe', function () use (&$failed) { $failed = true; });
    expect($failed)->toBeTrue();

    $rule->validate('username', 'john_doe', function () use (&$failed) { $failed = true; });
    expect($failed)->toBeTrue();

    $rule->validate('username', 'john-doe', function () use (&$failed) { $failed = true; });
    expect($failed)->toBeTrue();
});

test('fails for too short usernames', function () {
    $rule = new SystemUsername;
    $failed = false;
    $rule->validate('username', '', function () use (&$failed) { $failed = true; });
    expect($failed)->toBeTrue();
});

test('fails for too long usernames', function () {
    $rule = new SystemUsername;
    $failed = false;
    $rule->validate('username', str_repeat('a', 31), function () use (&$failed) { $failed = true; });
    expect($failed)->toBeTrue();
});

test('passes for exactly 30 character username', function () {
    $rule = new SystemUsername;
    $passed = true;
    $rule->validate('username', str_repeat('a', 30), function () use (&$passed) { $passed = false; });
    expect($passed)->toBeTrue();
});

test('fails for non-string value', function () {
    $rule = new SystemUsername;
    $failed = false;
    $rule->validate('username', 12345, function () use (&$failed) { $failed = true; });
    expect($failed)->toBeTrue();
});
