<?php

declare(strict_types=1);

use App\Modules\Settings\Enums\SettingType;

/*
|--------------------------------------------------------------------------
| YB22J — Settings Infrastructure — SettingType enum (spec-driven)
|--------------------------------------------------------------------------
*/

describe('YB22J: SettingType enum', function (): void {
    test('YB22J-FR-S7: exposes all seven supported type cases', function () {
        expect(SettingType::cases())->toHaveCount(7);

        expect(SettingType::STRING->value)->toBe('string')
            ->and(SettingType::INTEGER->value)->toBe('integer')
            ->and(SettingType::FLOAT->value)->toBe('float')
            ->and(SettingType::BOOLEAN->value)->toBe('boolean')
            ->and(SettingType::JSON->value)->toBe('json')
            ->and(SettingType::ENCRYPTED->value)->toBe('encrypted')
            ->and(SettingType::NULL->value)->toBe('null');
    });

    test('YB22J-FR-S7: detect maps boolean to BOOLEAN', function () {
        expect(SettingType::detect(true))->toBe(SettingType::BOOLEAN);
    });

    test('YB22J-FR-S7: detect maps integer to INTEGER', function () {
        expect(SettingType::detect(42))->toBe(SettingType::INTEGER);
    });

    test('YB22J-FR-S7: detect maps float to FLOAT', function () {
        expect(SettingType::detect(3.14))->toBe(SettingType::FLOAT);
    });

    test('YB22J-FR-S7: detect maps array to JSON', function () {
        expect(SettingType::detect(['a' => 1]))->toBe(SettingType::JSON);
    });

    test('YB22J-FR-S7: detect maps null to NULL', function () {
        expect(SettingType::detect(null))->toBe(SettingType::NULL);
    });

    test('YB22J-FR-S7: detect maps strings to STRING by default', function () {
        expect(SettingType::detect('text'))->toBe(SettingType::STRING)
            ->and(SettingType::detect('5'))->toBe(SettingType::STRING);
    });

    test('YB22J-FR-S7: detect trampoline orders bool before int', function () {
        // A bool is also not an int, but ensure distinct types are not conflated
        expect(SettingType::detect(true))->not->toBe(SettingType::INTEGER);
    });
});
