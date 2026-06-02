<?php

declare(strict_types=1);

use App\Domain\Settings\Actions\BatchSetSettingAction;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Support\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

describe('BatchSetSettingAction', function () {
    beforeEach(function () {
        Settings::clearOverrides();
        Cache::flush();
    });

    it('sets multiple simple values', function () {
        $results = app(BatchSetSettingAction::class)->execute([
            'key_one' => 'value_one',
            'key_two' => 'value_two',
        ]);

        expect($results)->toHaveCount(2);
        expect(Settings::get('key_one'))->toBe('value_one');
        expect(Settings::get('key_two'))->toBe('value_two');
    });

    it('handles array config with value and group', function () {
        app(BatchSetSettingAction::class)->execute([
            'mail_host' => ['value' => 'smtp.mailtrap.io', 'group' => 'mail'],
        ]);

        $setting = Setting::where('key', 'mail_host')->first();

        expect($setting->value)->toBe('smtp.mailtrap.io');
        expect($setting->group)->toBe('mail');
    });

    it('uses default group when not specified', function () {
        app(BatchSetSettingAction::class)->execute([
            'simple_key' => 'simple_value',
        ]);

        $setting = Setting::where('key', 'simple_key')->first();

        expect($setting->group)->toBe('general');
    });

    it('returns collection of Setting models', function () {
        $results = app(BatchSetSettingAction::class)->execute([
            'a' => '1',
            'b' => ['value' => '2', 'group' => 'test'],
        ]);

        expect($results)->toBeInstanceOf(Collection::class);
        expect($results->every(fn ($item) => $item instanceof Setting))->toBeTrue();
    });
});
