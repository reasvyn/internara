<?php

declare(strict_types=1);

use App\Modules\Settings\Entities\SettingEntity;
use App\Modules\Settings\Enums\SettingType;
use Illuminate\Database\Eloquent\Model;

final class SettingEntityModelDouble extends Model
{
    protected $guarded = [];
}

describe('YB22J: setting entity', function (): void {
    test('YB22J-FR-SET-001: fromModel bridges key, value, type, and group without persisting', function (): void {
        $model = new SettingEntityModelDouble([
            'key' => 'school.name',
            'value' => 'SMK Negeri 1',
            'type' => 'string',
            'group' => 'school',
        ]);

        $entity = SettingEntity::fromModel($model);

        expect($entity->key())->toBe('school.name');
        expect($entity->value())->toBe('SMK Negeri 1');
        expect($entity->type())->toBe('string');
        expect($entity->group())->toBe('school');
        expect($entity->isString())->toBeTrue();
        expect($entity->belongsToGroup('school'))->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('YB22J-FR-SET-002: settingType resolves known types and nulls unknown ones', function (): void {
        $boolean = SettingEntity::fromArray(['key' => 'k', 'value' => true, 'type' => 'boolean', 'group' => null]);
        $unknown = SettingEntity::fromArray(['key' => 'k', 'value' => 'x', 'type' => 'weird', 'group' => null]);
        $typeless = SettingEntity::fromArray(['key' => 'k', 'value' => 'x', 'type' => null, 'group' => null]);

        expect($boolean->settingType())->toBe(SettingType::BOOLEAN);
        expect($unknown->settingType())->toBeNull();
        expect($typeless->settingType())->toBeNull();
        expect($boolean->isType(SettingType::BOOLEAN))->toBeTrue();
        expect($boolean->isType(SettingType::STRING))->toBeFalse();
    });

    test('YB22J-FR-SET-002: boolean helpers coerce stored values', function (): void {
        $on = SettingEntity::fromArray(['key' => 'k', 'value' => '1', 'type' => 'boolean', 'group' => null]);
        $off = SettingEntity::fromArray(['key' => 'k', 'value' => '', 'type' => 'string', 'group' => null]);

        expect($on->isBoolean())->toBeTrue();
        expect($on->booleanValue())->toBeTrue();
        expect($off->isBoolean())->toBeFalse();
        expect($off->booleanValue())->toBeFalse();
    });

    test('YB22J-FR-SET-002: json helpers pass arrays through and reject scalars', function (): void {
        $json = SettingEntity::fromArray(['key' => 'k', 'value' => ['a' => 1], 'type' => 'json', 'group' => null]);
        $scalar = SettingEntity::fromArray(['key' => 'k', 'value' => 'nope', 'type' => 'json', 'group' => null]);

        expect($json->isJson())->toBeTrue();
        expect($json->jsonValue())->toBe(['a' => 1]);
        expect($scalar->jsonValue())->toBe([]);
    });

    test('YB22J-FR-SET-002: numeric helpers cast stored values', function (): void {
        $int = SettingEntity::fromArray(['key' => 'k', 'value' => '42', 'type' => 'integer', 'group' => null]);
        $float = SettingEntity::fromArray(['key' => 'k', 'value' => '4.5', 'type' => 'float', 'group' => null]);
        $encrypted = SettingEntity::fromArray(['key' => 'k', 'value' => 'cipher', 'type' => 'encrypted', 'group' => null]);

        expect($int->isInteger())->toBeTrue();
        expect($int->intValue())->toBe(42);
        expect($float->isFloat())->toBeTrue();
        expect($float->floatValue())->toBe(4.5);
        expect($encrypted->isEncrypted())->toBeTrue();
    });

    test('YB22J-FR-SET-001: isEmpty and group helpers answer the stored shape', function (): void {
        $nullValue = SettingEntity::fromArray(['key' => 'k', 'value' => null, 'type' => null, 'group' => 'school']);
        $blank = SettingEntity::fromArray(['key' => 'k', 'value' => '', 'type' => 'string', 'group' => 'school']);
        $filled = SettingEntity::fromArray(['key' => 'k', 'value' => 'x', 'type' => 'string', 'group' => 'school']);

        expect($nullValue->isEmpty())->toBeTrue();
        expect($blank->isEmpty())->toBeTrue();
        expect($filled->isEmpty())->toBeFalse();
        expect($filled->belongsToGroup('other'))->toBeFalse();
        expect($filled->isThemeColor(['brand.primary']))->toBeFalse();
        expect($filled->isThemeColor(['k']))->toBeTrue();
    });

    test('YB22J-FR-SET-001: fromArray rejects a missing key', function (): void {
        expect(fn (): SettingEntity => SettingEntity::fromArray(['value' => 'x', 'type' => null, 'group' => null]))
            ->toThrow(InvalidArgumentException::class, 'key');
    });

    test('YB22J-FR-SET-001: toArray, equals, and with round-trip by value', function (): void {
        $entity = SettingEntity::fromArray(['key' => 'k', 'value' => 'v', 'type' => 'string', 'group' => 'g']);

        expect($entity->toArray())->toBe(['key' => 'k', 'value' => 'v', 'type' => 'string', 'group' => 'g']);
        expect($entity->equals(SettingEntity::fromArray(['key' => 'k', 'value' => 'v', 'type' => 'string', 'group' => 'g'])))->toBeTrue();

        $updated = $entity->with('value', 'v2');

        expect($updated->value())->toBe('v2');
        expect($entity->value())->toBe('v');
    });
});
