<?php

declare(strict_types=1);

use App\Guidance\Handbook\Enums\HandbookAudience;

test('handbook audience has label for every case', function () {
    foreach (HandbookAudience::cases() as $case) {
        expect($case->label())->toBeString();
    }
});

test('handbook audience label returns translation', function () {
    expect(HandbookAudience::ALL->label())->toBe(__('guidance.audience_all'));
    expect(HandbookAudience::STUDENT->label())->toBe(__('guidance.audience_student'));
});
