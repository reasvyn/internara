<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;

describe('7C5WM: InternshipStatus enum', function (): void {
    test('7C5WM-FR-LIFE-013: cases carry the specified backing values', function (): void {
        expect(InternshipStatus::DRAFT->value)->toBe('draft');
        expect(InternshipStatus::from('draft'))->toBe(InternshipStatus::DRAFT);
        expect(InternshipStatus::PUBLISHED->value)->toBe('published');
        expect(InternshipStatus::from('published'))->toBe(InternshipStatus::PUBLISHED);
        expect(InternshipStatus::ACTIVE->value)->toBe('active');
        expect(InternshipStatus::from('active'))->toBe(InternshipStatus::ACTIVE);
        expect(InternshipStatus::COMPLETED->value)->toBe('completed');
        expect(InternshipStatus::from('completed'))->toBe(InternshipStatus::COMPLETED);
        expect(InternshipStatus::CANCELLED->value)->toBe('cancelled');
        expect(InternshipStatus::from('cancelled'))->toBe(InternshipStatus::CANCELLED);
        expect(InternshipStatus::ARCHIVED->value)->toBe('archived');
        expect(InternshipStatus::from('archived'))->toBe(InternshipStatus::ARCHIVED);
        expect(InternshipStatus::cases())->toHaveCount(6);
        expect(InternshipStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('7C5WM-FR-LIFE-013: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(InternshipStatus::DRAFT->label())->toBe('Draft');
        expect(InternshipStatus::PUBLISHED->label())->toBe('Published');
        expect(InternshipStatus::ACTIVE->label())->toBe('Active');
        expect(InternshipStatus::COMPLETED->label())->toBe('Completed');
        expect(InternshipStatus::CANCELLED->label())->toBe('Cancelled');
        expect(InternshipStatus::ARCHIVED->label())->toBe('Archived');
    });

    test('7C5WM-FR-LIFE-013: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(InternshipStatus::DRAFT->label())->toBe('Draf');
        expect(InternshipStatus::PUBLISHED->label())->toBe('Diterbitkan');
        expect(InternshipStatus::ACTIVE->label())->toBe('Aktif');
        expect(InternshipStatus::COMPLETED->label())->toBe('Selesai');
        expect(InternshipStatus::CANCELLED->label())->toBe('Dibatalkan');
        expect(InternshipStatus::ARCHIVED->label())->toBe('Diarsipkan');
    });
    test('7C5WM-FR-LIFE-007: published and active accept registrations, the rest do not', function (): void {
        expect(InternshipStatus::PUBLISHED->isAcceptingRegistrations())->toBeTrue();
        expect(InternshipStatus::ACTIVE->isAcceptingRegistrations())->toBeTrue();
        expect(InternshipStatus::DRAFT->isAcceptingRegistrations())->toBeFalse();
        expect(InternshipStatus::COMPLETED->isAcceptingRegistrations())->toBeFalse();
        expect(InternshipStatus::CANCELLED->isAcceptingRegistrations())->toBeFalse();
    });

    test('7C5WM-FR-LIFE-017: completed, cancelled, and archived are terminal', function (): void {
        expect(InternshipStatus::COMPLETED->isTerminal())->toBeTrue();
        expect(InternshipStatus::CANCELLED->isTerminal())->toBeTrue();
        expect(InternshipStatus::ARCHIVED->isTerminal())->toBeTrue();
        expect(InternshipStatus::DRAFT->isTerminal())->toBeFalse();
        expect(InternshipStatus::PUBLISHED->isTerminal())->toBeFalse();
        expect(InternshipStatus::ACTIVE->isTerminal())->toBeFalse();
    });

    test('7C5WM-FR-LIFE-014: validTransitions pins the lifecycle map', function (): void {
        expect(InternshipStatus::DRAFT->validTransitions())->toBe([InternshipStatus::PUBLISHED, InternshipStatus::CANCELLED]);
        expect(InternshipStatus::PUBLISHED->validTransitions())->toBe([InternshipStatus::ACTIVE, InternshipStatus::CANCELLED]);
        expect(InternshipStatus::ACTIVE->validTransitions())->toBe([InternshipStatus::COMPLETED, InternshipStatus::CANCELLED]);
        expect(InternshipStatus::COMPLETED->validTransitions())->toBe([InternshipStatus::ARCHIVED]);
        expect(InternshipStatus::CANCELLED->validTransitions())->toBe([]);
        expect(InternshipStatus::ARCHIVED->validTransitions())->toBe([]);
    });

    test('7C5WM-FR-LIFE-014: status updates validate through canTransitionTo', function (): void {
        expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::PUBLISHED))->toBeTrue();
        expect(InternshipStatus::PUBLISHED->canTransitionTo(InternshipStatus::ACTIVE))->toBeTrue();
        expect(InternshipStatus::ACTIVE->canTransitionTo(InternshipStatus::COMPLETED))->toBeTrue();
        expect(InternshipStatus::COMPLETED->canTransitionTo(InternshipStatus::ARCHIVED))->toBeTrue();
        expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::ACTIVE))->toBeFalse();
        expect(InternshipStatus::COMPLETED->canTransitionTo(InternshipStatus::DRAFT))->toBeFalse();
        expect(InternshipStatus::ARCHIVED->canTransitionTo(InternshipStatus::COMPLETED))->toBeFalse();
        expect(InternshipStatus::ARCHIVED->canTransitionTo(InternshipStatus::DRAFT))->toBeFalse();
        expect(InternshipStatus::CANCELLED->canTransitionTo(InternshipStatus::ARCHIVED))->toBeFalse();
        expect(InternshipStatus::DRAFT->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
