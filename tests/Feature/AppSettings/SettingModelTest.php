<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Database\QueryException;

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

it('throws when key is empty', function () {
    Setting::create(['key' => '', 'value' => 'x']);
})->throws(InvalidArgumentException::class, 'Setting key must not be empty.');

it('throws when key has invalid format', function () {
    Setting::create(['key' => 'UPPERCASE_KEY']);
})->throws(InvalidArgumentException::class, 'Setting key must be lowercase');

it('throws when key starts with uppercase', function () {
    Setting::create(['key' => 'Key_with_uppercase']);
})->throws(InvalidArgumentException::class, 'Setting key must be lowercase');

it('throws when key contains spaces', function () {
    Setting::create(['key' => 'my key']);
})->throws(InvalidArgumentException::class, 'Setting key must be lowercase');

it('throws when type is invalid', function () {
    Setting::create(['key' => 'valid_key', 'value' => 'x', 'type' => 'invalid_type']);
})->throws(InvalidArgumentException::class, 'Setting type must be one of');

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

    $groups = Setting::groups();

    expect($groups)->toContain('mail', 'general');
    expect($groups)->toHaveCount(2);
});

it('returns unique key per setting', function () {
    Setting::create(['key' => 'unique_key', 'value' => 'a']);

    expect(fn () => Setting::create(['key' => 'unique_key', 'value' => 'b']))
        ->toThrow(QueryException::class);
});

describe('upsertBatch', function () {
    it('returns 0 for empty input', function () {
        expect(Setting::upsertBatch([]))->toBe(0);
    });

    it('inserts new settings', function () {
        $count = Setting::upsertBatch([
            'first_key' => 'value1',
            'second_key' => 'value2',
        ]);

        expect($count)->toBe(2);
        expect(Setting::count())->toBe(2);
    });

    it('updates existing settings and returns count of changes', function () {
        Setting::create(['key' => 'existing', 'value' => 'old', 'type' => 'string']);

        $count = Setting::upsertBatch([
            'existing' => 'new_value',
        ]);

        expect($count)->toBe(1);
        expect(Setting::byKey('existing')->first()->value)->toBe('new_value');
    });

    it('handles array attributes with metadata', function () {
        $count = Setting::upsertBatch([
            'with_meta' => [
                'value' => 'the_value',
                'group' => 'custom_group',
                'description' => 'Has metadata',
            ],
        ]);

        expect($count)->toBe(1);

        $setting = Setting::byKey('with_meta')->first();
        expect($setting->value)->toBe('the_value');
        expect($setting->group)->toBe('custom_group');
        expect($setting->description)->toBe('Has metadata');
    });

    it('does not increment count when nothing changes', function () {
        Setting::create(['key' => 'stable', 'value' => 'same']);

        $count = Setting::upsertBatch(['stable' => 'same']);

        expect($count)->toBe(0);
    });
});
