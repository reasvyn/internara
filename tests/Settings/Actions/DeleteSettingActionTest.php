<?php

declare(strict_types=1);

use App\Modules\Settings\Actions\DeleteSettingAction;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| YB22J — Settings Infrastructure — DeleteSettingAction (spec-driven)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Settings::clearOverrides();
    Cache::flush();
});

describe('YB22J: DeleteSettingAction', function (): void {
    test('YB22J-FR-S5: removes a setting by single key', function () {
        $setting = Setting::create([
            'key' => 'delete.me',
            'value' => 'x',
            'type' => 'string',
            'group' => 'general',
        ]);

        $this->assertModelExists($setting);

        $deleted = app(DeleteSettingAction::class)->execute('delete.me');

        expect($deleted)->toBe(1)
            ->and(Setting::find('delete.me'))->toBeNull();
    });

    test('YB22J-FR-S5: removes settings by array of keys', function () {
        Setting::create(['key' => 'del.a', 'value' => 'a', 'type' => 'string', 'group' => 'general']);
        Setting::create(['key' => 'del.b', 'value' => 'b', 'type' => 'string', 'group' => 'general']);

        $deleted = app(DeleteSettingAction::class)->execute(['del.a', 'del.b']);

        expect($deleted)->toBe(2)
            ->and(Setting::find('del.a'))->toBeNull()
            ->and(Setting::find('del.b'))->toBeNull();
    });

    test('YB22J-FR-S5: invalidates settings_key cache on delete', function () {
        Setting::create(['key' => 'del.cache', 'value' => 'x', 'type' => 'string', 'group' => 'general']);
        Cache::put(config('cache-keys.settings_key').'del.cache', 'cached');

        app(DeleteSettingAction::class)->execute('del.cache');

        expect(Cache::has(config('cache-keys.settings_key').'del.cache'))->toBeFalse();
    });

    test('YB22J-FR-S5: invalidates settings_all cache on delete', function () {
        Setting::create(['key' => 'del.all', 'value' => 'x', 'type' => 'string', 'group' => 'general']);
        Cache::put(config('cache-keys.settings_all'), 'cached');

        app(DeleteSettingAction::class)->execute('del.all');

        expect(Cache::has(config('cache-keys.settings_all')))->toBeFalse();
    });

    test('YB22J-FR-S5: invalidates settings_group cache on delete', function () {
        Setting::create(['key' => 'del.group', 'value' => 'x', 'type' => 'string', 'group' => 'general']);
        Cache::put(config('cache-keys.settings_group').'general', 'cached');

        app(DeleteSettingAction::class)->execute('del.group');

        expect(Cache::has(config('cache-keys.settings_group').'general'))->toBeFalse();
    });
});
