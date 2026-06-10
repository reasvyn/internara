<?php

declare(strict_types=1);

use App\Incident\IncidentReport\Enums\IncidentSeverity;

test('incident severity has all expected cases', function () {
    $cases = IncidentSeverity::cases();

    expect($cases)->toHaveCount(4);
    expect(IncidentSeverity::LOW->value)->toBe('low');
    expect(IncidentSeverity::MEDIUM->value)->toBe('medium');
    expect(IncidentSeverity::HIGH->value)->toBe('high');
    expect(IncidentSeverity::CRITICAL->value)->toBe('critical');
});

test('incident severity label returns non-empty string', function () {
    foreach (IncidentSeverity::cases() as $severity) {
        expect($severity->label())->toBeString()->not->toBeEmpty();
    }
});
