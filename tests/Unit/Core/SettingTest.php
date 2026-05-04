<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

uses(LazilyRefreshDatabase::class);

test('scopeGroup filters by group name', function () {
    Setting::create(['key' => 'mail.host', 'value' => 'smtp.example.com', 'type' => 'string', 'group' => 'mail']);
    Setting::create(['key' => 'mail.port', 'value' => '587', 'type' => 'integer', 'group' => 'mail']);
    Setting::create(['key' => 'app.name', 'value' => 'MyApp', 'type' => 'string', 'group' => 'app']);

    $mailSettings = Setting::group('mail')->get();

    expect($mailSettings)->toHaveCount(2);

    $mailSettings->each(fn ($s) => expect($s->group)->toBe('mail'));
});

test('scopeByKey filters by key', function () {
    Setting::create(['key' => 'unique.key', 'value' => 'found', 'type' => 'string']);
    Setting::create(['key' => 'other.key', 'value' => 'not-found', 'type' => 'string']);

    $result = Setting::byKey('unique.key')->first();

    expect($result)->not->toBeNull()
        ->and($result->value)->toBe('found');
});

test('saving throws exception for empty key', function () {
    Log::shouldReceive('error')->once();

    Setting::create(['key' => '', 'value' => 'test', 'type' => 'string']);
})->throws(InvalidArgumentException::class, 'Setting key must not be empty.');

test('setting has uuid trait', function () {
    $setting = Setting::create(['key' => 'uuid.test', 'value' => 'value', 'type' => 'string']);

    expect($setting->id)->toBeString()
        ->and(strlen($setting->id))->toBe(36);
});

test('setting value cast is applied', function () {
    $setting = Setting::create([
        'key' => 'cast.test',
        'value' => ['foo' => 'bar'],
        'type' => 'json',
    ]);

    expect($setting->value)->toBe(['foo' => 'bar']);
});

test('setting is fillable for expected fields', function () {
    $setting = Setting::create([
        'key' => 'fillable.test',
        'value' => 'test-value',
        'type' => 'string',
        'description' => 'Test description',
        'group' => 'test_group',
    ]);

    expect($setting->key)->toBe('fillable.test')
        ->and($setting->value)->toBe('test-value')
        ->and($setting->type)->toBe('string')
        ->and($setting->description)->toBe('Test description')
        ->and($setting->group)->toBe('test_group');
});

test('setting factory creates valid model', function () {
    $setting = Setting::factory()->create();

    expect($setting->id)->toBeString()
        ->and($setting->key)->toBeString()
        ->and($setting->type)->toBeString();
});

test('setting factory creates encrypted state', function () {
    $setting = Setting::factory()->encrypted()->create();

    expect($setting->type)->toBe('encrypted')
        ->and($setting->value)->not->toBeEmpty();
});
