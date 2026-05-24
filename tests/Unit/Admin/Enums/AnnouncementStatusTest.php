<?php

declare(strict_types=1);

use App\Domain\Admin\Enums\AnnouncementStatus;
use App\Domain\Core\Contracts\LabelEnum;

describe('AnnouncementStatus', function () {
    it('is string-backed', function () {
        expect(AnnouncementStatus::DRAFT->value)->toBe('draft');
    });

    it('implements LabelEnum', function () {
        expect(AnnouncementStatus::DRAFT)->toBeInstanceOf(LabelEnum::class);
    });

    it('draft can transition to scheduled', function () {
        expect(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::SCHEDULED))->toBeTrue();
    });

    it('draft can transition to published', function () {
        expect(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeTrue();
    });

    it('scheduled can transition to published', function () {
        expect(AnnouncementStatus::SCHEDULED->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeTrue();
    });

    it('published cannot transition', function () {
        expect(AnnouncementStatus::PUBLISHED->canTransitionTo(AnnouncementStatus::DRAFT))->toBeFalse()
            ->and(AnnouncementStatus::PUBLISHED->canTransitionTo(AnnouncementStatus::SCHEDULED))->toBeFalse();
    });

    it('scheduled cannot go back to draft', function () {
        expect(AnnouncementStatus::SCHEDULED->canTransitionTo(AnnouncementStatus::DRAFT))->toBeFalse();
    });

    it('default is draft', function () {
        expect(AnnouncementStatus::default())->toBe(AnnouncementStatus::DRAFT);
    });
});
