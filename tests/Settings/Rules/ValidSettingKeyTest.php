<?php

declare(strict_types=1);

use App\Settings\Rules\ValidSettingKey;
use Illuminate\Support\Facades\Validator;

test('valid setting keys pass validation', function (string $key) {
    $validator = Validator::make(['key' => $key], ['key' => new ValidSettingKey]);

    expect($validator->passes())->toBeTrue();
})->with([
    'simple',
    'with_underscore',
    'with.dots',
    'mixed123',
    'a',
    'nested.key.here',
    'group.setting_name',
]);

test('invalid setting keys fail validation', function (string $key) {
    $validator = Validator::make(['key' => $key], ['key' => ['required', new ValidSettingKey]]);

    expect($validator->fails())->toBeTrue();
})->with([
    'UPPERCASE',
    'has spaces',
    'with-hyphen',
    '123starting_with_number',
    '',
    'special@char',
]);
