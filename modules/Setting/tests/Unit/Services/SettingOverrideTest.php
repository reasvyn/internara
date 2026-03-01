<?php

declare(strict_types=1);


use Modules\Setting\Services\Contracts\SettingService;



test('setting helper can be overridden for testing', function () {
    // Ensure we are using the Setting module's implementation
    $service = app(SettingService::class);

    $key = 'test_setting_override';
    $originalValue = setting($key, 'original');

    // Set override
    setting()->override([$key => 'overridden']);

    expect(setting($key))->toBe('overridden');

    // Clear override
    setting()->clearOverrides();

    expect(setting($key, 'original'))->toBe('original');
});
