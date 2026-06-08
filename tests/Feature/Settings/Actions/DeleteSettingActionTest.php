<?php

declare(strict_types=1);

use App\Settings\Actions\DeleteSettingAction;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('delete setting action deletes a single key', function () {
    Setting::create(['key' => 'test_key', 'value' => 'val', 'type' => 'string']);

    $action = new DeleteSettingAction;
    $deleted = $action->execute('test_key');

    expect($deleted)->toBe(1);
    expect(Setting::where('key', 'test_key')->exists())->toBeFalse();
});

test('delete setting action deletes multiple keys', function () {
    Setting::create(['key' => 'key_a', 'value' => '1', 'type' => 'string']);
    Setting::create(['key' => 'key_b', 'value' => '2', 'type' => 'string']);

    $action = new DeleteSettingAction;
    $deleted = $action->execute(['key_a', 'key_b']);

    expect($deleted)->toBe(2);
});

test('delete setting action returns zero for non-existent keys', function () {
    $action = new DeleteSettingAction;
    $deleted = $action->execute('non_existent');

    expect($deleted)->toBe(0);
});

test('delete setting action is transactional', function () {
    Setting::create(['key' => 'key_a', 'value' => '1', 'type' => 'string']);

    $action = new DeleteSettingAction;
    $deleted = $action->execute(['key_a', 'non_existent']);

    expect($deleted)->toBe(1);
});
