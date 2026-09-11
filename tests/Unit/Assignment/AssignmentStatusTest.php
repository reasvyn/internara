<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Enums\AssignmentStatus;

describe('T657Z: AssignmentStatus enum', function (): void {
    test('T657Z-FR-ASG-006: cases carry the specified backing values', function (): void {
        expect(AssignmentStatus::DRAFT->value)->toBe('draft');
        expect(AssignmentStatus::from('draft'))->toBe(AssignmentStatus::DRAFT);
        expect(AssignmentStatus::PUBLISHED->value)->toBe('published');
        expect(AssignmentStatus::from('published'))->toBe(AssignmentStatus::PUBLISHED);
        expect(AssignmentStatus::CLOSED->value)->toBe('closed');
        expect(AssignmentStatus::from('closed'))->toBe(AssignmentStatus::CLOSED);
        expect(AssignmentStatus::cases())->toHaveCount(3);
        expect(AssignmentStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('T657Z-FR-ASG-006: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AssignmentStatus::DRAFT->label())->toBe('Draft');
        expect(AssignmentStatus::PUBLISHED->label())->toBe('Published');
        expect(AssignmentStatus::CLOSED->label())->toBe('Closed');
    });

    test('T657Z-FR-ASG-006: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AssignmentStatus::DRAFT->label())->toBe('Draf');
        expect(AssignmentStatus::PUBLISHED->label())->toBe('Diterbitkan');
        expect(AssignmentStatus::CLOSED->label())->toBe('Ditutup');
    });
    test('T657Z-FR-ASG-006: isActive is true only for published', function (): void {
        expect(AssignmentStatus::PUBLISHED->isActive())->toBeTrue();
        expect(AssignmentStatus::DRAFT->isActive())->toBeFalse();
        expect(AssignmentStatus::CLOSED->isActive())->toBeFalse();
    });

    test('T657Z-FR-ASG-006: closed is the only terminal state', function (): void {
        expect(AssignmentStatus::CLOSED->isTerminal())->toBeTrue();
        expect(AssignmentStatus::DRAFT->isTerminal())->toBeFalse();
        expect(AssignmentStatus::PUBLISHED->isTerminal())->toBeFalse();
    });

    test('T657Z-FR-ASG-006: validTransitions pins the publish/close map', function (): void {
        expect(AssignmentStatus::DRAFT->validTransitions())->toBe([AssignmentStatus::PUBLISHED, AssignmentStatus::CLOSED]);
        expect(AssignmentStatus::PUBLISHED->validTransitions())->toBe([AssignmentStatus::CLOSED]);
        expect(AssignmentStatus::CLOSED->validTransitions())->toBe([]);
    });

    test('T657Z-FR-ASG-006: canTransitionTo allows the mapped moves and nothing else', function (): void {
        expect(AssignmentStatus::DRAFT->canTransitionTo(AssignmentStatus::PUBLISHED))->toBeTrue();
        expect(AssignmentStatus::DRAFT->canTransitionTo(AssignmentStatus::CLOSED))->toBeTrue();
        expect(AssignmentStatus::PUBLISHED->canTransitionTo(AssignmentStatus::CLOSED))->toBeTrue();
        expect(AssignmentStatus::PUBLISHED->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
        expect(AssignmentStatus::CLOSED->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
        expect(AssignmentStatus::DRAFT->canTransitionTo(SubmissionStatus::DRAFT))->toBeFalse();
    });
});
