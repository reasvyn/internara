<?php

declare(strict_types=1);

use App\Document\Handbook\Enums\HandbookAudience;

test('handbook audience has label for every case', function () {
    foreach (HandbookAudience::cases() as $case) {
        expect($case->label())->toBeString();
    }
});

test('handbook audience label returns translation', function () {
    expect(HandbookAudience::ALL->label())->toBe(__('handbook.audience_all'));
    expect(HandbookAudience::STUDENT->label())->toBe(__('handbook.audience_student'));
});
