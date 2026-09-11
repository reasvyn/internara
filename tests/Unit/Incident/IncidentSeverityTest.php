<?php

declare(strict_types=1);

use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentSeverity;

describe('3RU9S: IncidentSeverity enum', function (): void {
    test('3RU9S-FR-INC-006: cases carry the specified backing values', function (): void {
        expect(IncidentSeverity::LOW->value)->toBe('low');
        expect(IncidentSeverity::from('low'))->toBe(IncidentSeverity::LOW);
        expect(IncidentSeverity::MEDIUM->value)->toBe('medium');
        expect(IncidentSeverity::from('medium'))->toBe(IncidentSeverity::MEDIUM);
        expect(IncidentSeverity::HIGH->value)->toBe('high');
        expect(IncidentSeverity::from('high'))->toBe(IncidentSeverity::HIGH);
        expect(IncidentSeverity::CRITICAL->value)->toBe('critical');
        expect(IncidentSeverity::from('critical'))->toBe(IncidentSeverity::CRITICAL);
        expect(IncidentSeverity::cases())->toHaveCount(4);
        expect(IncidentSeverity::tryFrom('no-such-value'))->toBeNull();
    });
    test('3RU9S-FR-INC-006: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(IncidentSeverity::LOW->label())->toBe('Low');
        expect(IncidentSeverity::MEDIUM->label())->toBe('Medium');
        expect(IncidentSeverity::HIGH->label())->toBe('High');
        expect(IncidentSeverity::CRITICAL->label())->toBe('Critical');
    });

    test('3RU9S-FR-INC-006: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(IncidentSeverity::LOW->label())->toBe('Rendah');
        expect(IncidentSeverity::MEDIUM->label())->toBe('Sedang');
        expect(IncidentSeverity::HIGH->label())->toBe('Tinggi');
        expect(IncidentSeverity::CRITICAL->label())->toBe('Kritis');
    });
});
