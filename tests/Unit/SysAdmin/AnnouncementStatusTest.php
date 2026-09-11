<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;

describe('3S55V: AnnouncementStatus enum', function (): void {
    test('3S55V-FR-ANN-005: cases carry the specified backing values', function (): void {
        expect(AnnouncementStatus::DRAFT->value)->toBe('draft');
        expect(AnnouncementStatus::from('draft'))->toBe(AnnouncementStatus::DRAFT);
        expect(AnnouncementStatus::SCHEDULED->value)->toBe('scheduled');
        expect(AnnouncementStatus::from('scheduled'))->toBe(AnnouncementStatus::SCHEDULED);
        expect(AnnouncementStatus::PUBLISHED->value)->toBe('published');
        expect(AnnouncementStatus::from('published'))->toBe(AnnouncementStatus::PUBLISHED);
        expect(AnnouncementStatus::cases())->toHaveCount(3);
        expect(AnnouncementStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('3S55V-FR-ANN-005: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AnnouncementStatus::DRAFT->label())->toBe('Draft');
        expect(AnnouncementStatus::SCHEDULED->label())->toBe('Scheduled');
        expect(AnnouncementStatus::PUBLISHED->label())->toBe('Published');
    });

    test('3S55V-FR-ANN-005: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AnnouncementStatus::DRAFT->label())->toBe('Draf');
        expect(AnnouncementStatus::SCHEDULED->label())->toBe('Terjadwal');
        expect(AnnouncementStatus::PUBLISHED->label())->toBe('Terkirim');
    });
    test('3S55V-FR-ANN-006: published is the only terminal state and draft is the default', function (): void {
        expect(AnnouncementStatus::PUBLISHED->isTerminal())->toBeTrue();
        expect(AnnouncementStatus::DRAFT->isTerminal())->toBeFalse();
        expect(AnnouncementStatus::SCHEDULED->isTerminal())->toBeFalse();
        expect(AnnouncementStatus::default())->toBe(AnnouncementStatus::DRAFT);
    });

    test('3S55V-FR-ANN-006: validTransitions pins draft to scheduled or published', function (): void {
        expect(AnnouncementStatus::DRAFT->validTransitions())->toBe([AnnouncementStatus::SCHEDULED, AnnouncementStatus::PUBLISHED]);
        expect(AnnouncementStatus::SCHEDULED->validTransitions())->toBe([AnnouncementStatus::PUBLISHED]);
        expect(AnnouncementStatus::PUBLISHED->validTransitions())->toBe([]);
    });

    test('3S55V-FR-ANN-006: canTransitionTo enforces the map and rejects foreign enums', function (): void {
        expect(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::SCHEDULED))->toBeTrue();
        expect(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeTrue();
        expect(AnnouncementStatus::SCHEDULED->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeTrue();
        expect(AnnouncementStatus::SCHEDULED->canTransitionTo(AnnouncementStatus::DRAFT))->toBeFalse();
        expect(AnnouncementStatus::PUBLISHED->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeFalse();
        expect(AnnouncementStatus::DRAFT->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
