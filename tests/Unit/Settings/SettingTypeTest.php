<?php

declare(strict_types=1);

use App\Modules\Settings\Enums\SettingType;

describe('YB22J: SettingType enum', function (): void {
    test('YB22J-FR-SET-002: cases carry the specified backing values', function (): void {
        expect(SettingType::STRING->value)->toBe('string');
        expect(SettingType::from('string'))->toBe(SettingType::STRING);
        expect(SettingType::INTEGER->value)->toBe('integer');
        expect(SettingType::from('integer'))->toBe(SettingType::INTEGER);
        expect(SettingType::FLOAT->value)->toBe('float');
        expect(SettingType::from('float'))->toBe(SettingType::FLOAT);
        expect(SettingType::BOOLEAN->value)->toBe('boolean');
        expect(SettingType::from('boolean'))->toBe(SettingType::BOOLEAN);
        expect(SettingType::JSON->value)->toBe('json');
        expect(SettingType::from('json'))->toBe(SettingType::JSON);
        expect(SettingType::ENCRYPTED->value)->toBe('encrypted');
        expect(SettingType::from('encrypted'))->toBe(SettingType::ENCRYPTED);
        expect(SettingType::NULL->value)->toBe('null');
        expect(SettingType::from('null'))->toBe(SettingType::NULL);
        expect(SettingType::cases())->toHaveCount(7);
        expect(SettingType::tryFrom('no-such-value'))->toBeNull();
    });
    test('YB22J-FR-SET-002: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(SettingType::STRING->label())->toBe('String');
        expect(SettingType::INTEGER->label())->toBe('Integer');
        expect(SettingType::FLOAT->label())->toBe('Float');
        expect(SettingType::BOOLEAN->label())->toBe('Boolean');
        expect(SettingType::JSON->label())->toBe('JSON');
        expect(SettingType::ENCRYPTED->label())->toBe('Encrypted');
        expect(SettingType::NULL->label())->toBe('Null');
    });

    test('YB22J-FR-SET-002: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(SettingType::STRING->label())->toBe('Teks');
        expect(SettingType::INTEGER->label())->toBe('Bilangan Bulat');
        expect(SettingType::FLOAT->label())->toBe('Desimal');
        expect(SettingType::BOOLEAN->label())->toBe('Boolean');
        expect(SettingType::JSON->label())->toBe('JSON');
        expect(SettingType::ENCRYPTED->label())->toBe('Terenkripsi');
        expect(SettingType::NULL->label())->toBe('Null');
    });
    test('YB22J-FR-SET-002: values() lists every backing value in case order', function (): void {
        expect(SettingType::values())->toBe(['string', 'integer', 'float', 'boolean', 'json', 'encrypted', 'null']);
    });

    test('YB22J-FR-SET-002: detect() infers the storage type from the runtime value', function (): void {
        expect(SettingType::detect(true))->toBe(SettingType::BOOLEAN);
        expect(SettingType::detect(false))->toBe(SettingType::BOOLEAN);
        expect(SettingType::detect(5))->toBe(SettingType::INTEGER);
        expect(SettingType::detect(5.5))->toBe(SettingType::FLOAT);
        expect(SettingType::detect(['a' => 1]))->toBe(SettingType::JSON);
        expect(SettingType::detect(null))->toBe(SettingType::NULL);
        expect(SettingType::detect('hello'))->toBe(SettingType::STRING);
    });

    test('YB22J-FR-SET-002: cast() coerces stored values to their declared type', function (): void {
        expect(SettingType::INTEGER->cast('5'))->toBe(5);
        expect(SettingType::FLOAT->cast('5.5'))->toBe(5.5);
        expect(SettingType::BOOLEAN->cast('true'))->toBeTrue();
        expect(SettingType::BOOLEAN->cast('0'))->toBeFalse();
        expect(SettingType::JSON->cast('{"a":1}'))->toBe(['a' => 1]);
        expect(SettingType::JSON->cast(['a' => 1]))->toBe(['a' => 1]);
        expect(SettingType::NULL->cast('anything'))->toBeNull();
        expect(SettingType::STRING->cast(5))->toBe('5');
    });
});
