<?php

declare(strict_types=1);

use App\Document\Enums\DocumentCategory;

test('document category has all cases', function () {
    expect(DocumentCategory::cases())->toHaveCount(7);
    expect(DocumentCategory::APPLICATION->value)->toBe('application');
    expect(DocumentCategory::PERMIT->value)->toBe('permit');
    expect(DocumentCategory::CERTIFICATE->value)->toBe('certificate');
    expect(DocumentCategory::REPORT->value)->toBe('report');
    expect(DocumentCategory::LETTER->value)->toBe('letter');
});

test('document category labels are non-empty', function () {
    foreach (DocumentCategory::cases() as $c) {
        expect($c->label())->toBeString()->not->toBeEmpty();
    }
});
