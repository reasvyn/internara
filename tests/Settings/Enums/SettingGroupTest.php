<?php

declare(strict_types=1);

use App\Settings\Enums\SettingGroup;

test('setting group has all expected cases', function () {
    $cases = SettingGroup::cases();

    expect($cases)->toHaveCount(7);
    expect(SettingGroup::GENERAL->value)->toBe('general');
    expect(SettingGroup::MAIL->value)->toBe('mail');
    expect(SettingGroup::SYSTEM->value)->toBe('system');
    expect(SettingGroup::BRANDING->value)->toBe('branding');
    expect(SettingGroup::FEATURES->value)->toBe('features');
    expect(SettingGroup::LOCALIZATION->value)->toBe('localization');
    expect(SettingGroup::NOTIFICATIONS->value)->toBe('notifications');
});

test('default returns general', function () {
    expect(SettingGroup::default())->toBe(SettingGroup::GENERAL);
});

test('label returns translated string', function () {
    $label = SettingGroup::GENERAL->label();

    expect($label)->toBeString()->not->toBeEmpty();
});
