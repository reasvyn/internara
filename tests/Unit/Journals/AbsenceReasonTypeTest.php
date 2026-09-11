<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceReasonType;

describe('1KSWL: AbsenceReasonType enum', function (): void {
    test('1KSWL-FR-DAILY-009: cases carry the specified backing values', function (): void {
        expect(AbsenceReasonType::SICK->value)->toBe('sick');
        expect(AbsenceReasonType::from('sick'))->toBe(AbsenceReasonType::SICK);
        expect(AbsenceReasonType::PERMISSION->value)->toBe('permission');
        expect(AbsenceReasonType::from('permission'))->toBe(AbsenceReasonType::PERMISSION);
        expect(AbsenceReasonType::EMERGENCY->value)->toBe('emergency');
        expect(AbsenceReasonType::from('emergency'))->toBe(AbsenceReasonType::EMERGENCY);
        expect(AbsenceReasonType::OTHER->value)->toBe('other');
        expect(AbsenceReasonType::from('other'))->toBe(AbsenceReasonType::OTHER);
        expect(AbsenceReasonType::cases())->toHaveCount(4);
        expect(AbsenceReasonType::tryFrom('no-such-value'))->toBeNull();
    });
    test('1KSWL-FR-DAILY-009: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AbsenceReasonType::SICK->label())->toBe('Sick');
        expect(AbsenceReasonType::PERMISSION->label())->toBe('Permission');
        expect(AbsenceReasonType::EMERGENCY->label())->toBe('Emergency');
        expect(AbsenceReasonType::OTHER->label())->toBe('Other');
    });

    test('1KSWL-FR-DAILY-009: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AbsenceReasonType::SICK->label())->toBe('Sakit');
        expect(AbsenceReasonType::PERMISSION->label())->toBe('Izin');
        expect(AbsenceReasonType::EMERGENCY->label())->toBe('Darurat');
        expect(AbsenceReasonType::OTHER->label())->toBe('Lainnya');
    });
    test('1KSWL-FR-DAILY-012: sick and emergency demand an attachment, permission and other do not', function (): void {
        expect(AbsenceReasonType::SICK->requiresAttachment())->toBeTrue();
        expect(AbsenceReasonType::EMERGENCY->requiresAttachment())->toBeTrue();
        expect(AbsenceReasonType::PERMISSION->requiresAttachment())->toBeFalse();
        expect(AbsenceReasonType::OTHER->requiresAttachment())->toBeFalse();
    });
});
