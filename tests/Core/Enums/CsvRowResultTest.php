<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Core\Enums\CsvRowResult;

test('SE5Q9-FR-D5: every case has a non-empty translated label', function () {
    foreach (CsvRowResult::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
});

test('SE5Q9-FR-D5: implements the LabelEnum contract', function () {
    expect(CsvRowResult::class)->toImplement(LabelEnum::class);
});
