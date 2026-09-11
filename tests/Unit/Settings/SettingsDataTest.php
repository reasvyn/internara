<?php

declare(strict_types=1);

use App\Modules\Settings\Data\SettingData;
use App\Modules\Settings\Data\SettingEntryData;
use App\Modules\Settings\Data\SettingGroupData;
use App\Modules\Settings\Data\SystemSettingsData;

describe('YB22J: SettingData DTO', function (): void {
    test('YB22J-FR-SET-003: fromArray maps the key with open value and metadata defaults', function (): void {
        $dto = SettingData::fromArray(['key' => 'brand.name', 'value' => 'SMK Maju']);

        expect($dto->key)->toBe('brand.name');
        expect($dto->value)->toBe('SMK Maju');
        expect($dto->type)->toBeNull();
        expect($dto->group)->toBeNull();
        expect($dto->description)->toBeNull();
    });

    test('YB22J-FR-SET-003: fromArray throws when the key is missing', function (): void {
        expect(fn (): SettingData => SettingData::fromArray(['value' => 'x']))
            ->toThrow(InvalidArgumentException::class, 'key');
    });

    test('YB22J-FR-SET-003: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(SettingData::from(['key' => 'k', 'value' => 5])->value)->toBe(5);

        $source = new class
        {
            public function toArray(): array
            {
                return ['key' => 'k2', 'value' => ['nested' => true], 'group' => 'brand'];
            }
        };

        $dto = SettingData::from($source);

        expect($dto->key)->toBe('k2');
        expect($dto->group)->toBe('brand');
        expect(fn (): SettingData => SettingData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('YB22J-FR-SET-003: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new SettingData(key: 'k', value: 'v', type: 'string');

        expect($dto->toArray())->toBe(['key' => 'k', 'value' => 'v', 'type' => 'string', 'group' => null, 'description' => null]);
        expect($dto->only('key', 'value'))->toBe(['key' => 'k', 'value' => 'v']);
        expect($dto->except('type', 'group', 'description'))->toBe(['key' => 'k', 'value' => 'v']);

        $merged = $dto->merge(['value' => 'v-baru']);

        expect($merged->value)->toBe('v-baru');
        expect($dto->value)->toBe('v');
    });
});

describe('YB22J: SettingEntryData DTO', function (): void {
    test('YB22J-FR-SET-009: fromArray maps key and value with null metadata defaults', function (): void {
        $dto = SettingEntryData::fromArray(['key' => 'site.title', 'value' => 'Internara']);

        expect($dto->key)->toBe('site.title');
        expect($dto->value)->toBe('Internara');
        expect($dto->group)->toBeNull();
        expect($dto->description)->toBeNull();
        expect($dto->type)->toBeNull();
    });

    test('YB22J-FR-SET-009: fromArray throws when the value is missing', function (): void {
        expect(fn (): SettingEntryData => SettingEntryData::fromArray(['key' => 'site.title']))
            ->toThrow(InvalidArgumentException::class, 'value');
    });

    test('YB22J-FR-SET-009: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(SettingEntryData::from(['key' => 'k', 'value' => true])->value)->toBeTrue();

        $source = new class
        {
            public function toArray(): array
            {
                return ['key' => 'k2', 'value' => 10, 'type' => 'integer'];
            }
        };

        expect(SettingEntryData::from($source)->type)->toBe('integer');
        expect(fn (): SettingEntryData => SettingEntryData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('YB22J-FR-SET-009: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new SettingEntryData(key: 'k', value: 'v', group: 'site');

        expect($dto->toArray())->toBe(['key' => 'k', 'value' => 'v', 'group' => 'site', 'description' => null, 'type' => null]);
        expect($dto->only('key'))->toBe(['key' => 'k']);
        expect(array_key_exists('value', $dto->except('key')))->toBeTrue();

        $merged = $dto->merge(['group' => 'brand']);

        expect($merged->group)->toBe('brand');
        expect($dto->group)->toBe('site');
    });
});

describe('YB22J: SettingGroupData DTO', function (): void {
    test('fromArray maps the group name with a zero count default', function (): void {
        $dto = SettingGroupData::fromArray(['name' => 'brand']);

        expect($dto->name)->toBe('brand');
        expect($dto->count)->toBe(0);
    });

    test('fromArray throws when the group name is missing', function (): void {
        expect(fn (): SettingGroupData => SettingGroupData::fromArray(['count' => 3]))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(SettingGroupData::from(['name' => 'mail', 'count' => 4])->count)->toBe(4);

        $source = new class
        {
            public function toArray(): array
            {
                return ['name' => 'site'];
            }
        };

        expect(SettingGroupData::from($source)->count)->toBe(0);
        expect(fn (): SettingGroupData => SettingGroupData::from(null))->toThrow(InvalidArgumentException::class);
    });

    test('toArray, only, except, and merge shape the payload', function (): void {
        $dto = new SettingGroupData(name: 'brand', count: 7);

        expect($dto->toArray())->toBe(['name' => 'brand', 'count' => 7]);
        expect($dto->only('count'))->toBe(['count' => 7]);
        expect($dto->except('count'))->toBe(['name' => 'brand']);

        $merged = $dto->merge(['count' => 8]);

        expect($merged->count)->toBe(8);
        expect($dto->count)->toBe(7);
    });
});

describe('YB22J: SystemSettingsData DTO', function (): void {
    test('YB22J-FR-SET-011: fromArray defaults every setting to its empty fallback', function (): void {
        $dto = SystemSettingsData::fromArray([]);

        expect($dto->brandName)->toBe('');
        expect($dto->defaultLocale)->toBe('id');
        expect($dto->mailPort)->toBe('587');
        expect($dto->mailEncryption)->toBe('tls');
        expect($dto->brandLogo)->toBeNull();
        expect($dto->siteFavicon)->toBeNull();
        expect($dto->mailPassword)->toBeNull();
    });

    test('YB22J-FR-SET-011: fromArray accepts snake_case keys', function (): void {
        $dto = SystemSettingsData::fromArray([
            'brand_name' => 'SMK Maju',
            'site_title' => 'PKL',
            'default_locale' => 'en',
            'support_email' => 'info@smk.id',
            'mail_host' => 'smtp.smk.id',
            'mail_port' => '465',
        ]);

        expect($dto->brandName)->toBe('SMK Maju');
        expect($dto->siteTitle)->toBe('PKL');
        expect($dto->defaultLocale)->toBe('en');
        expect($dto->supportEmail)->toBe('info@smk.id');
        expect($dto->mailHost)->toBe('smtp.smk.id');
        expect($dto->mailPort)->toBe('465');
    });

    test('YB22J-FR-SET-011: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(SystemSettingsData::from(['brandName' => 'SMK'])->brandName)->toBe('SMK');

        $source = new class
        {
            public function toArray(): array
            {
                return ['mailUsername' => 'admin@smk.id'];
            }
        };

        expect(SystemSettingsData::from($source)->mailUsername)->toBe('admin@smk.id');
        expect(fn (): SystemSettingsData => SystemSettingsData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('YB22J-FR-SET-011: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new SystemSettingsData(brandName: 'SMK Maju', defaultLocale: 'en');

        expect($dto->toArray()['brandName'])->toBe('SMK Maju');
        expect($dto->only('brandName', 'defaultLocale'))->toBe(['brandName' => 'SMK Maju', 'defaultLocale' => 'en']);
        expect(array_key_exists('mailHost', $dto->except('brandName')))->toBeTrue();

        $merged = $dto->merge(['defaultLocale' => 'id']);

        expect($merged->defaultLocale)->toBe('id');
        expect($dto->defaultLocale)->toBe('en');
    });
});
