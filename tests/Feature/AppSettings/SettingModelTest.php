<?php

declare(strict_types=1);

use App\Actions\Admin\SetSettingAction;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

it('can create a setting with valid attributes', function () {
    $setting = Setting::create([
        'key' => 'test_key',
        'value' => 'test_value',
        'type' => 'string',
        'group' => 'testing',
        'description' => 'A test setting',
    ]);

    expect($setting)->toBeInstanceOf(Setting::class);
    expect($setting->key)->toBe('test_key');
    expect($setting->value)->toBe('test_value');
    expect($setting->type)->toBe('string');
    expect($setting->group)->toBe('testing');
});

it('validates key through action', function () {
    $action = app(SetSettingAction::class);

    expect(fn () => $action->execute('', 'x'))->toThrow(ValidationException::class);
    expect(fn () => $action->execute('UPPERCASE_KEY', 'x'))->toThrow(ValidationException::class);
    expect(fn () => $action->execute('Key_with_uppercase', 'x'))->toThrow(ValidationException::class);
    expect(fn () => $action->execute('my key', 'x'))->toThrow(ValidationException::class);
});

it('allows valid key formats with dots and underscores', function () {
    $setting = Setting::create([
        'key' => 'app.debug_mode',
        'value' => '1',
        'type' => 'boolean',
    ]);

    expect($setting->key)->toBe('app.debug_mode');
});

it('defaults type to string when not provided', function () {
    $setting = Setting::create([
        'key' => 'no_type_given',
        'value' => 'hello',
    ]);

    expect($setting->type)->toBe('string');
});

it('can update an existing setting', function () {
    $setting = Setting::factory()->create(['value' => 'old']);

    $setting->update(['value' => 'new']);

    expect($setting->fresh()->value)->toBe('new');
});

it('can delete a setting', function () {
    $setting = Setting::factory()->create();

    $setting->delete();

    expect(Setting::find($setting->id))->toBeNull();
});

it('can query by group scope', function () {
    Setting::factory()->create(['group' => 'mail', 'key' => 'mail_host']);
    Setting::factory()->create(['group' => 'mail', 'key' => 'mail_port']);
    Setting::factory()->create(['group' => 'general', 'key' => 'site_name']);

    $mailSettings = Setting::group('mail')->get();

    expect($mailSettings)->toHaveCount(2);
    expect($mailSettings->pluck('key'))->toContain('mail_host', 'mail_port');
});

it('can query by key scope', function () {
    Setting::factory()->create(['key' => 'unique_key_123']);

    $setting = Setting::byKey('unique_key_123')->first();

    expect($setting)->not->toBeNull();
    expect($setting->key)->toBe('unique_key_123');
});

it('can query by inGroup scope with multiple groups', function () {
    Setting::factory()->create(['group' => 'mail', 'key' => 'a']);
    Setting::factory()->create(['group' => 'general', 'key' => 'b']);
    Setting::factory()->create(['group' => 'auth', 'key' => 'c']);

    $results = Setting::inGroup(['mail', 'auth'])->get();

    expect($results)->toHaveCount(2);
});

it('can query by type scope', function () {
    Setting::factory()->boolean()->create(['key' => 'flag']);
    Setting::factory()->integer()->create(['key' => 'count']);
    Setting::factory()->string()->create(['key' => 'label']);

    $bools = Setting::ofType('boolean')->get();
    expect($bools)->toHaveCount(1);
    expect($bools->first()->key)->toBe('flag');
});

it('can search by key or description', function () {
    Setting::factory()->create(['key' => 'smtp_host', 'description' => 'SMTP server host']);
    Setting::factory()->create(['key' => 'app_url', 'description' => 'Application URL']);

    $results = Setting::searchable('smtp')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->key)->toBe('smtp_host');
});

it('can list distinct groups', function () {
    Setting::factory()->create(['group' => 'mail']);
    Setting::factory()->create(['group' => 'general']);
    Setting::factory()->create(['group' => 'mail']);

    $groups = Setting::query()
        ->select('group')
        ->distinct()
        ->whereNotNull('group')
        ->orderBy('group')
        ->pluck('group');

    expect($groups)->toContain('mail', 'general');
    expect($groups)->toHaveCount(2);
});

it('returns unique key per setting', function () {
    Setting::create(['key' => 'unique_key', 'value' => 'a']);

    expect(fn () => Setting::create(['key' => 'unique_key', 'value' => 'b']))
        ->toThrow(QueryException::class);
});

describe('batch upsert', function () {
    it('inserts new settings via updateOrCreate', function () {
        Setting::updateOrCreate(['key' => 'first_key'], ['value' => 'value1']);
        Setting::updateOrCreate(['key' => 'second_key'], ['value' => 'value2']);

        expect(Setting::count())->toBe(2);
    });

    it('updates existing settings', function () {
        Setting::create(['key' => 'existing', 'value' => 'old', 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'existing'], ['value' => 'new_value']);

        expect(Setting::byKey('existing')->first()->value)->toBe('new_value');
    });

    it('handles array attributes with metadata', function () {
        Setting::updateOrCreate(
            ['key' => 'with_meta'],
            ['value' => 'the_value', 'group' => 'custom_group', 'description' => 'Has metadata'],
        );

        $setting = Setting::byKey('with_meta')->first();
        expect($setting->value)->toBe('the_value');
        expect($setting->group)->toBe('custom_group');
        expect($setting->description)->toBe('Has metadata');
    });
});
