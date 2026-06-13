<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Enums\AnnouncementStatus;

test('has expected cases', function () {
    expect(AnnouncementStatus::DRAFT->value)->toBe('draft');
    expect(AnnouncementStatus::SCHEDULED->value)->toBe('scheduled');
    expect(AnnouncementStatus::PUBLISHED->value)->toBe('published');
});

test('label returns translated string', function () {
    expect(AnnouncementStatus::DRAFT->label())->toBe(__('announcement.status.draft'));
    expect(AnnouncementStatus::SCHEDULED->label())->toBe(__('announcement.status.scheduled'));
    expect(AnnouncementStatus::PUBLISHED->label())->toBe(__('announcement.status.published'));
});

test('default returns draft', function () {
    expect(AnnouncementStatus::default())->toBe(AnnouncementStatus::DRAFT);
});

test('can transition to allowed targets', function () {
    expect(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::SCHEDULED))->toBeTrue();
    expect(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeTrue();
    expect(AnnouncementStatus::SCHEDULED->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeTrue();
});

test('cannot transition to disallowed targets', function () {
    expect(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::DRAFT))->toBeFalse();
    expect(AnnouncementStatus::SCHEDULED->canTransitionTo(AnnouncementStatus::DRAFT))->toBeFalse();
    expect(AnnouncementStatus::SCHEDULED->canTransitionTo(AnnouncementStatus::SCHEDULED))->toBeFalse();
    expect(AnnouncementStatus::PUBLISHED->canTransitionTo(AnnouncementStatus::DRAFT))->toBeFalse();
    expect(AnnouncementStatus::PUBLISHED->canTransitionTo(AnnouncementStatus::SCHEDULED))->toBeFalse();
    expect(AnnouncementStatus::PUBLISHED->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeFalse();
});
