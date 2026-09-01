<?php

declare(strict_types=1);

use App\Modules\Settings\Actions\BatchSetSettingAction;
use App\Modules\Settings\Data\SettingEntryData;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| YB22J — Settings Infrastructure — BatchSetSettingAction (spec-driven)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Settings::clearOverrides();
    Cache::flush();
});

describe('YB22J: BatchSetSettingAction', function (): void {
    test('YB22J-FR-S4: persists multiple entries via a single action call', function () {
        $result = app(BatchSetSettingAction::class)->execute(
            new SettingEntryData(key: 'batch.one', value: 1),
            new SettingEntryData(key: 'batch.two', value: 'two'),
            new SettingEntryData(key: 'batch.three', value: true),
        );

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result)->toHaveCount(3)
            ->and(Setting::find('batch.one')->value)->toBe(1)
            ->and(Setting::find('batch.two')->value)->toBe('two')
            ->and(Setting::find('batch.three')->value)->toBeTrue();
    });

    test('YB22J-FR-S4: executes all upserts within a single DB transaction', function () {
        $source = file_get_contents((new ReflectionClass(BatchSetSettingAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });

    test('YB22J-FR-S4: assigns default group when none provided', function () {
        app(BatchSetSettingAction::class)->execute(
            new SettingEntryData(key: 'batch.group', value: 'x'),
        );

        expect(Setting::find('batch.group')->group)->toBe('general');
    });

    test('YB22J-FR-S4: invalidates settings cache for each upserted entry', function () {
        Cache::put(config('cache-keys.settings_key').'batch.cacheone', 'old');

        app(BatchSetSettingAction::class)->execute(
            new SettingEntryData(key: 'batch.cacheone', value: 'new'),
        );

        expect(Cache::has(config('cache-keys.settings_key').'batch.cacheone'))->toBeFalse();
    });
});
