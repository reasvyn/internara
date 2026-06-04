<?php

declare(strict_types=1);

use App\Domain\SysAdmin\Aggregates\Setting\Support\AppInfo;
use App\Domain\SysAdmin\Aggregates\Setting\Support\AppMetadata;
use App\Domain\SysAdmin\Aggregates\Setting\Support\Settings;

test('setting helper returns settings class when no argument is passed', function () {
    expect(setting())->toBeInstanceOf(Settings::class);
});

test('setting helper retrieves values from settings support class', function () {
    $result = setting('non_existent_key_for_test', 'default_test_val');
    expect($result)->toBe('default_test_val');
});

test('brand helper retrieves brand settings from metadata support class', function () {
    $result = brand('version');
    expect($result)->toBe(AppMetadata::version());
});

test('app_info helper retrieves application info details', function () {
    expect(app_info('version'))->toBe(AppInfo::version());
    expect(app_info())->toBe(AppInfo::all());
});
