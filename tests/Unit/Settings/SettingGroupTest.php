<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Settings\Enums\SettingGroup;

describe('SettingGroup', function () {
    it('has general group', function () {
        expect(SettingGroup::GENERAL->value)->toBe('general');
    });

    it('has mail group', function () {
        expect(SettingGroup::MAIL->value)->toBe('mail');
    });

    it('has system group', function () {
        expect(SettingGroup::SYSTEM->value)->toBe('system');
    });

    it('has branding group', function () {
        expect(SettingGroup::BRANDING->value)->toBe('branding');
    });

    it('has features group', function () {
        expect(SettingGroup::FEATURES->value)->toBe('features');
    });

    it('has localization group', function () {
        expect(SettingGroup::LOCALIZATION->value)->toBe('localization');
    });

    it('has notifications group', function () {
        expect(SettingGroup::NOTIFICATIONS->value)->toBe('notifications');
    });

    it('implements LabelEnum', function () {
        $reflection = new ReflectionClass(SettingGroup::class);

        expect($reflection->implementsInterface(LabelEnum::class))->toBeTrue();
    });

    it('returns translated label', function () {
        $label = SettingGroup::GENERAL->label();

        expect($label)->toBeString();
    });

    it('has default method returning GENERAL', function () {
        expect(SettingGroup::default())->toBe(SettingGroup::GENERAL);
    });
});
