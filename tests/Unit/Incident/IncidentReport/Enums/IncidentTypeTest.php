<?php

declare(strict_types=1);

use App\Incident\IncidentReport\Enums\IncidentType;

test('incident type has all expected cases', function () {
    $cases = IncidentType::cases();

    expect($cases)->toHaveCount(5);
    expect(IncidentType::ACCIDENT->value)->toBe('accident');
    expect(IncidentType::SAFETY_VIOLATION->value)->toBe('safety_violation');
    expect(IncidentType::HARASSMENT->value)->toBe('harassment');
    expect(IncidentType::DISCIPLINARY->value)->toBe('disciplinary');
    expect(IncidentType::OTHER->value)->toBe('other');
});

test('incident type label returns non-empty string', function () {
    foreach (IncidentType::cases() as $type) {
        expect($type->label())->toBeString()->not->toBeEmpty();
    }
});
