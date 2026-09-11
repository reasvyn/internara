<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionType;

describe('2EHSE: SupervisionType enum', function (): void {
    test('2EHSE-FR-SUPV-002: cases carry the specified backing values', function (): void {
        expect(SupervisionType::GUIDANCE->value)->toBe('guidance');
        expect(SupervisionType::from('guidance'))->toBe(SupervisionType::GUIDANCE);
        expect(SupervisionType::SUPERVISORING->value)->toBe('mentoring');
        expect(SupervisionType::from('mentoring'))->toBe(SupervisionType::SUPERVISORING);
        expect(SupervisionType::MONITORING->value)->toBe('monitoring');
        expect(SupervisionType::from('monitoring'))->toBe(SupervisionType::MONITORING);
        expect(SupervisionType::cases())->toHaveCount(3);
        expect(SupervisionType::tryFrom('no-such-value'))->toBeNull();
    });
    test('2EHSE-FR-SUPV-002: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(SupervisionType::GUIDANCE->label())->toBe('Guidance');
        expect(SupervisionType::SUPERVISORING->label())->toBe('Mentoring');
        expect(SupervisionType::MONITORING->label())->toBe('Monitoring');
    });

    test('2EHSE-FR-SUPV-002: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(SupervisionType::GUIDANCE->label())->toBe('Bimbingan');
        expect(SupervisionType::SUPERVISORING->label())->toBe('Pendampingan');
        expect(SupervisionType::MONITORING->label())->toBe('Pemantauan');
    });
    test('2EHSE-FR-SUPV-002: supervising keeps the mentoring stored value', function (): void {
        expect(SupervisionType::SUPERVISORING->value)->toBe('mentoring');
        expect(SupervisionType::from('mentoring'))->toBe(SupervisionType::SUPERVISORING);
    });
});
