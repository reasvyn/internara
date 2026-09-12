<?php

declare(strict_types=1);

use App\Modules\Settings\Enums\SettingGroup;

describe('YB22J: SettingGroup enum', function (): void {
    test('YB22J-FR-SET-001: cases carry the specified backing values', function (): void {
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
    test('YB22J-FR-SET-001: general is the default group', function (): void {
        expect(SettingGroup::default())->toBe(SettingGroup::GENERAL);
        expect(SettingGroup::from('general'))->toBe(SettingGroup::GENERAL);
    });

    test('YB22J-NFR-SET-009: stored groups resolve translated labels in English', function (): void {
        app()->setLocale('en');
        expect(SettingGroup::GENERAL->label())->toBe('General Configuration');
        expect(SettingGroup::MAIL->label())->toBe('Mail Services');
        expect(SettingGroup::SYSTEM->label())->toBe('System Information');
        expect(SettingGroup::BRANDING->label())->toBe('Branding & Appearance');
        expect(SettingGroup::FEATURES->label())->toBe('Feature Flags');
        expect(SettingGroup::LOCALIZATION->label())->toBe('Localization');
        expect(SettingGroup::NOTIFICATIONS->label())->toBe('Notifications');
    });

    test('YB22J-NFR-SET-009: stored groups resolve translated labels in Indonesian', function (): void {
        app()->setLocale('id');
        expect(SettingGroup::GENERAL->label())->toBe('Konfigurasi Umum');
        expect(SettingGroup::MAIL->label())->toBe('Layanan Email');
        expect(SettingGroup::SYSTEM->label())->toBe('Informasi Sistem');
        expect(SettingGroup::BRANDING->label())->toBe('Branding & Tampilan');
        expect(SettingGroup::FEATURES->label())->toBe('Flag Fitur');
        expect(SettingGroup::LOCALIZATION->label())->toBe('Lokalisasi');
        expect(SettingGroup::NOTIFICATIONS->label())->toBe('Notifikasi');
    });
});
