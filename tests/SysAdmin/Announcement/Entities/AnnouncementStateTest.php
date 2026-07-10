<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Entities\AnnouncementState;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use Carbon\Carbon;

test('is published returns true when status is published', function () {
    $state = new AnnouncementState(AnnouncementStatus::PUBLISHED, null);

    expect($state->isPublished())->toBeTrue();
});

test('is published returns false when status is draft', function () {
    $state = new AnnouncementState(AnnouncementStatus::DRAFT, null);

    expect($state->isPublished())->toBeFalse();
});

test('is draft returns true when status is draft', function () {
    $state = new AnnouncementState(AnnouncementStatus::DRAFT, null);

    expect($state->isDraft())->toBeTrue();
});

test('is scheduled returns true when status is scheduled', function () {
    $state = new AnnouncementState(AnnouncementStatus::SCHEDULED, null);

    expect($state->isScheduled())->toBeTrue();
});

test('is pending publish returns true when scheduled at is in the past', function () {
    $state = new AnnouncementState(AnnouncementStatus::SCHEDULED, Carbon::yesterday());

    expect($state->isPendingPublish())->toBeTrue();
});

test('is pending publish returns false when scheduled at is in the future', function () {
    $state = new AnnouncementState(AnnouncementStatus::SCHEDULED, Carbon::tomorrow());

    expect($state->isPendingPublish())->toBeFalse();
});

test('is pending publish returns false when status is not scheduled', function () {
    $state = new AnnouncementState(AnnouncementStatus::DRAFT, Carbon::yesterday());

    expect($state->isPendingPublish())->toBeFalse();
});

test('is pending publish returns false when scheduled at is null', function () {
    $state = new AnnouncementState(AnnouncementStatus::SCHEDULED, null);

    expect($state->isPendingPublish())->toBeFalse();
});

test('is pending publish accepts custom now time', function () {
    $state = new AnnouncementState(AnnouncementStatus::SCHEDULED, Carbon::parse('2025-01-15'));

    expect($state->isPendingPublish(Carbon::parse('2025-01-20')))->toBeTrue();
    expect($state->isPendingPublish(Carbon::parse('2025-01-10')))->toBeFalse();
});
