<?php

declare(strict_types=1);

use App\Modules\Settings\Entities\SettingEntity;
use App\Modules\Settings\Enums\SettingType;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| YB22J — Settings Infrastructure — SettingEntity (spec-driven)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Settings::clearOverrides();
    Cache::flush();
});

describe('YB22J: SettingEntity', function (): void {
    test('YB22J-FR-S2: fromModel builds an entity with key, value, type and group', function () {
        $setting = Setting::create([
            'key' => 'entity.meta',
            'value' => 'v',
            'type' => 'string',
            'group' => 'general',
        ]);

        $entity = $setting->asSetting();

        expect($entity)->toBeInstanceOf(SettingEntity::class)
            ->and($entity->key())->toBe('entity.meta')
            ->and($entity->value())->toBe('v')
            ->and($entity->type())->toBe('string')
            ->and($entity->group())->toBe('general');
    });

    test('YB22J-FR-S2: booleanValue returns bool for boolean settings', function () {
        $entity = Setting::create([
            'key' => 'entity.flag',
            'value' => true,
            'type' => 'boolean',
        ])->asSetting();

        expect($entity->booleanValue())->toBeTrue()
            ->and($entity->isBoolean())->toBeTrue();
    });

    test('YB22J-FR-S2: intValue returns int for integer settings', function () {
        $entity = Setting::create([
            'key' => 'entity.count',
            'value' => 10,
            'type' => 'integer',
        ])->asSetting();

        expect($entity->intValue())->toBe(10)
            ->and($entity->isInteger())->toBeTrue();
    });

    test('YB22J-FR-S2: floatValue returns float for float settings', function () {
        $entity = Setting::create([
            'key' => 'entity.ratio',
            'value' => 1.5,
            'type' => 'float',
        ])->asSetting();

        expect($entity->floatValue())->toBe(1.5)
            ->and($entity->isFloat())->toBeTrue();
    });

    test('YB22J-FR-S2: jsonValue returns array for json settings', function () {
        $entity = Setting::create([
            'key' => 'entity.payload',
            'value' => ['a' => 1],
            'type' => 'json',
        ])->asSetting();

        expect($entity->jsonValue())->toBe(['a' => 1])
            ->and($entity->isJson())->toBeTrue();
    });

    test('YB22J-FR-S2: isEmpty is true for empty string values', function () {
        $entity = Setting::create([
            'key' => 'entity.empty',
            'value' => '',
            'type' => 'string',
        ])->asSetting();

        expect($entity->isEmpty())->toBeTrue();
    });

    test('YB22J-FR-S2: isEmpty is false for non-empty values', function () {
        $entity = Setting::create([
            'key' => 'entity.nonempty',
            'value' => 'text',
            'type' => 'string',
        ])->asSetting();

        expect($entity->isEmpty())->toBeFalse();
    });

    test('YB22J-FR-S7: settingType maps string column to enum via tryFrom', function () {
        $entity = Setting::create([
            'key' => 'entity.typebool',
            'value' => true,
            'type' => 'boolean',
        ])->asSetting();

        expect($entity->settingType())->toBe(SettingType::BOOLEAN)
            ->and($entity->isType(SettingType::BOOLEAN))->toBeTrue();
    });

    test('YB22J-FR-S7: settingType returns string type for non-boolean', function () {
        $entity = Setting::create([
            'key' => 'entity.typestr',
            'value' => 'x',
            'type' => 'string',
        ])->asSetting();

        expect($entity->settingType())->toBe(SettingType::STRING)
            ->and($entity->isString())->toBeTrue();
    });

    test('YB22J-FR-S2: belongsToGroup reports group membership', function () {
        $entity = Setting::create([
            'key' => 'entity.group',
            'value' => 'x',
            'type' => 'string',
            'group' => 'mail',
        ])->asSetting();

        expect($entity->belongsToGroup('mail'))->toBeTrue()
            ->and($entity->belongsToGroup('general'))->toBeFalse();
    });
});
