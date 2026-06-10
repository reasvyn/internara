<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Enums\SupervisionType;

test('supervision type has all expected cases', function () {
    $cases = SupervisionType::cases();

    expect($cases)->toHaveCount(3);
    expect(SupervisionType::GUIDANCE->value)->toBe('guidance');
    expect(SupervisionType::SUPERVISORING->value)->toBe('mentoring');
    expect(SupervisionType::MONITORING->value)->toBe('monitoring');
});

test('supervision type label returns non-empty string', function () {
    foreach (SupervisionType::cases() as $type) {
        expect($type->label())->toBeString()->not->toBeEmpty();
    }
});
