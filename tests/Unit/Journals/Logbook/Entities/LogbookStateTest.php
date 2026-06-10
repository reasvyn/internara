<?php

declare(strict_types=1);

use App\Journals\Logbook\Entities\LogbookState;
use App\Journals\Logbook\Enums\LogbookStatus;

test('logbook state detects verified', function () {
    $verified = new LogbookState(LogbookStatus::VERIFIED);
    expect($verified->isVerified())->toBeTrue();

    $draft = new LogbookState(LogbookStatus::DRAFT);
    expect($draft->isVerified())->toBeFalse();
});

test('logbook state can be edited for draft and revision required', function () {
    expect((new LogbookState(LogbookStatus::DRAFT))->canBeEdited())->toBeTrue();
    expect((new LogbookState(LogbookStatus::REVISION_REQUIRED))->canBeEdited())->toBeTrue();
    expect((new LogbookState(LogbookStatus::SUBMITTED))->canBeEdited())->toBeFalse();
    expect((new LogbookState(LogbookStatus::VERIFIED))->canBeEdited())->toBeFalse();
});
