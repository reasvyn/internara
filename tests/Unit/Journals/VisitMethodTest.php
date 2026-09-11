<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\MonitoringVisit\Enums\VisitMethod;

describe('2EHSE: VisitMethod enum', function (): void {
    test('2EHSE-FR-SUPV-007: cases carry the specified backing values', function (): void {
        expect(VisitMethod::SITE_VISIT->value)->toBe('site_visit');
        expect(VisitMethod::from('site_visit'))->toBe(VisitMethod::SITE_VISIT);
        expect(VisitMethod::VIRTUAL_MEETING->value)->toBe('virtual_meeting');
        expect(VisitMethod::from('virtual_meeting'))->toBe(VisitMethod::VIRTUAL_MEETING);
        expect(VisitMethod::PHONE_CALL->value)->toBe('phone_call');
        expect(VisitMethod::from('phone_call'))->toBe(VisitMethod::PHONE_CALL);
        expect(VisitMethod::cases())->toHaveCount(3);
        expect(VisitMethod::tryFrom('no-such-value'))->toBeNull();
    });
    test('2EHSE-FR-SUPV-007: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(VisitMethod::SITE_VISIT->label())->toBe('Site Visit');
        expect(VisitMethod::VIRTUAL_MEETING->label())->toBe('Virtual Meeting');
        expect(VisitMethod::PHONE_CALL->label())->toBe('Phone Call');
    });

    test('2EHSE-FR-SUPV-007: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(VisitMethod::SITE_VISIT->label())->toBe('Kunjungan Lapangan');
        expect(VisitMethod::VIRTUAL_MEETING->label())->toBe('Pertemuan Virtual');
        expect(VisitMethod::PHONE_CALL->label())->toBe('Telepon');
    });
});
