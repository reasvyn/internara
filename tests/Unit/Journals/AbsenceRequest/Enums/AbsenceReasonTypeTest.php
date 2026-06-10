<?php

declare(strict_types=1);

use App\Journals\AbsenceRequest\Enums\AbsenceReasonType;

test('absence reason type has all cases', function () {
    expect(AbsenceReasonType::cases())->toHaveCount(4);
    expect(AbsenceReasonType::SICK->value)->toBe('sick');
    expect(AbsenceReasonType::PERMISSION->value)->toBe('permission');
    expect(AbsenceReasonType::EMERGENCY->value)->toBe('emergency');
    expect(AbsenceReasonType::OTHER->value)->toBe('other');
});

test('absence reason labels are non-empty', function () {
    foreach (AbsenceReasonType::cases() as $r) {
        expect($r->label())->toBeString()->not->toBeEmpty();
    }
});

test('sick and emergency require attachment', function () {
    expect(AbsenceReasonType::SICK->requiresAttachment())->toBeTrue();
    expect(AbsenceReasonType::EMERGENCY->requiresAttachment())->toBeTrue();
    expect(AbsenceReasonType::PERMISSION->requiresAttachment())->toBeFalse();
    expect(AbsenceReasonType::OTHER->requiresAttachment())->toBeFalse();
});
