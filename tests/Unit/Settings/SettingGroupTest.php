<?php

declare(strict_types=1);

use App\Modules\Settings\Enums\SettingGroup;

describe('SettingGroup enum', function (): void {
    test('cases carry the specified backing values: cases carry the specified backing values', function (): void {
        expect(SettingGroup::GENERAL->value)->toBe('general');
        expect(SettingGroup::from('general'))->toBe(SettingGroup::GENERAL);
        expect(SettingGroup::MAIL->value)->toBe('mail');
        expect(SettingGroup::from('mail'))->toBe(SettingGroup::MAIL);
        expect(SettingGroup::SYSTEM->value)->toBe('system');
        expect(SettingGroup::from('system'))->toBe(SettingGroup::SYSTEM);
        expect(SettingGroup::BRANDING->value)->toBe('branding');
        expect(SettingGroup::from('branding'))->toBe(SettingGroup::BRANDING);
        expect(SettingGroup::FEATURES->value)->toBe('features');
        expect(SettingGroup::from('features'))->toBe(SettingGroup::FEATURES);
        expect(SettingGroup::LOCALIZATION->value)->toBe('localization');
        expect(SettingGroup::from('localization'))->toBe(SettingGroup::LOCALIZATION);
        expect(SettingGroup::NOTIFICATIONS->value)->toBe('notifications');
        expect(SettingGroup::from('notifications'))->toBe(SettingGroup::NOTIFICATIONS);
        expect(SettingGroup::cases())->toHaveCount(7);
        expect(SettingGroup::tryFrom('no-such-value'))->toBeNull();
    });
    test('general is the default group', function (): void {
        expect(SettingGroup::default())->toBe(SettingGroup::GENERAL);
        expect(SettingGroup::from('general'))->toBe(SettingGroup::GENERAL);
    });

    test('stored groups resolve translated labels in both locales', function (): void {
        app()->setLocale('en');
        expect(SettingGroup::GENERAL->label())->toBe('General Configuration');
        expect(SettingGroup::MAIL->label())->toBe('Mail Services');
        expect(SettingGroup::SYSTEM->label())->toBe('System Information');

        app()->setLocale('id');
        expect(SettingGroup::GENERAL->label())->toBe('Konfigurasi Umum');
        expect(SettingGroup::MAIL->label())->toBe('Layanan Email');
        expect(SettingGroup::SYSTEM->label())->toBe('Informasi Sistem');
    });

    // NOTE: BRANDING, FEATURES, LOCALIZATION, and NOTIFICATIONS labels are not
    // asserted — lang/{en,id}/setting.php has no groups.* keys for them, so __()
    // returns the raw key. Recorded as drift for review.
});
