<?php

declare(strict_types=1);

use App\Settings\Enums\SettingType;

test('setting type has all expected cases', function () {
    $cases = SettingType::cases();

    expect($cases)->toHaveCount(7);
    expect(SettingType::STRING->value)->toBe('string');
    expect(SettingType::INTEGER->value)->toBe('integer');
    expect(SettingType::FLOAT->value)->toBe('float');
    expect(SettingType::BOOLEAN->value)->toBe('boolean');
    expect(SettingType::JSON->value)->toBe('json');
    expect(SettingType::ENCRYPTED->value)->toBe('encrypted');
    expect(SettingType::NULL->value)->toBe('null');
});

test('label returns non-empty string for each type', function () {
    foreach (SettingType::cases() as $type) {
        expect($type->label())->toBeString()->not->toBeEmpty();
    }
});

test('detect returns correct type for various values', function () {
    expect(SettingType::detect(true))->toBe(SettingType::BOOLEAN);
    expect(SettingType::detect(false))->toBe(SettingType::BOOLEAN);
    expect(SettingType::detect(42))->toBe(SettingType::INTEGER);
    expect(SettingType::detect(3.14))->toBe(SettingType::FLOAT);
    expect(SettingType::detect(['a' => 1]))->toBe(SettingType::JSON);
    expect(SettingType::detect(null))->toBe(SettingType::NULL);
    expect(SettingType::detect('hello'))->toBe(SettingType::STRING);
    expect(SettingType::detect(''))->toBe(SettingType::STRING);
});

test('values returns all value strings', function () {
    $values = SettingType::values();

    expect($values)->toContain('string', 'integer', 'float', 'boolean', 'json', 'encrypted', 'null');
    expect($values)->toHaveCount(7);
});

test('cast converts values correctly', function () {
    expect(SettingType::BOOLEAN->cast('1'))->toBeTrue();
    expect(SettingType::BOOLEAN->cast(0))->toBeFalse();
    expect(SettingType::INTEGER->cast('42'))->toBe(42);
    expect(SettingType::FLOAT->cast('3.14'))->toBe(3.14);
    expect(SettingType::STRING->cast(42))->toBe('42');
    expect(SettingType::NULL->cast('anything'))->toBeNull();
});

test('cast json decodes string to array', function () {
    $result = SettingType::JSON->cast('{"a":1,"b":2}');

    expect($result)->toBe(['a' => 1, 'b' => 2]);
});

test('cast json returns array for non-string', function () {
    $result = SettingType::JSON->cast(['direct' => 'array']);

    expect($result)->toBe(['direct' => 'array']);
});
