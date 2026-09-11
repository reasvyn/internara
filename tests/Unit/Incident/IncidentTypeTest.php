<?php

declare(strict_types=1);

use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentType;

describe('3RU9S: IncidentType enum', function (): void {
    test('3RU9S-FR-INC-006: cases carry the specified backing values', function (): void {
        expect(IncidentType::ACCIDENT->value)->toBe('accident');
        expect(IncidentType::from('accident'))->toBe(IncidentType::ACCIDENT);
        expect(IncidentType::SAFETY_VIOLATION->value)->toBe('safety_violation');
        expect(IncidentType::from('safety_violation'))->toBe(IncidentType::SAFETY_VIOLATION);
        expect(IncidentType::HARASSMENT->value)->toBe('harassment');
        expect(IncidentType::from('harassment'))->toBe(IncidentType::HARASSMENT);
        expect(IncidentType::DISCIPLINARY->value)->toBe('disciplinary');
        expect(IncidentType::from('disciplinary'))->toBe(IncidentType::DISCIPLINARY);
        expect(IncidentType::OTHER->value)->toBe('other');
        expect(IncidentType::from('other'))->toBe(IncidentType::OTHER);
        expect(IncidentType::cases())->toHaveCount(5);
        expect(IncidentType::tryFrom('no-such-value'))->toBeNull();
    });
    test('3RU9S-FR-INC-006: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(IncidentType::ACCIDENT->label())->toBe('Accident');
        expect(IncidentType::SAFETY_VIOLATION->label())->toBe('Safety Violation');
        expect(IncidentType::HARASSMENT->label())->toBe('Harassment');
        expect(IncidentType::DISCIPLINARY->label())->toBe('Disciplinary');
        expect(IncidentType::OTHER->label())->toBe('Other');
    });

    test('3RU9S-FR-INC-006: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(IncidentType::ACCIDENT->label())->toBe('Kecelakaan');
        expect(IncidentType::SAFETY_VIOLATION->label())->toBe('Pelanggaran K3');
        expect(IncidentType::HARASSMENT->label())->toBe('Pelecehan');
        expect(IncidentType::DISCIPLINARY->label())->toBe('Pelanggaran Disiplin');
        expect(IncidentType::OTHER->label())->toBe('Lainnya');
    });
});
