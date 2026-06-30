<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Entities;

use App\Settings\Models\Setting;
use App\Setup\Entities\SetupEntity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('update uses known type from map for known keys', function () {
    SetupEntity::update(['is_installed' => true]);

    $record = Setting::where('key', 'setup.is_installed')->first();
    expect($record->type)->toBe('boolean');
});

test('update falls back to boolean type for bool values with unknown key', function () {
    SetupEntity::update(['some_custom_flag' => false]);

    $record = Setting::where('key', 'setup.some_custom_flag')->first();
    expect($record->type)->toBe('boolean');
});

test('update falls back to json type for array values with unknown key', function () {
    SetupEntity::update(['custom_list' => ['a', 'b']]);

    $record = Setting::where('key', 'setup.custom_list')->first();
    expect($record->type)->toBe('json');
});

test('update falls back to integer type for int values with unknown key', function () {
    SetupEntity::update(['custom_count' => 42]);

    $record = Setting::where('key', 'setup.custom_count')->first();
    expect($record->type)->toBe('integer');
});

test('update falls back to string type for string values with unknown key', function () {
    SetupEntity::update(['custom_label' => 'hello']);

    $record = Setting::where('key', 'setup.custom_label')->first();
    expect($record->type)->toBe('string');
});
