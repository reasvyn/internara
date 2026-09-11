<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Reports\Domain\StudentReport\Enums\StudentReportStatus;

describe('StudentReportStatus enum', function (): void {
    test('cases carry the specified backing values: cases carry the specified backing values', function (): void {
        expect(StudentReportStatus::DRAFT->value)->toBe('draft');
        expect(StudentReportStatus::from('draft'))->toBe(StudentReportStatus::DRAFT);
        expect(StudentReportStatus::FINALIZED->value)->toBe('finalized');
        expect(StudentReportStatus::from('finalized'))->toBe(StudentReportStatus::FINALIZED);
        expect(StudentReportStatus::cases())->toHaveCount(2);
        expect(StudentReportStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('labels resolve in both locales: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(StudentReportStatus::DRAFT->label())->toBe('Draft');
        expect(StudentReportStatus::FINALIZED->label())->toBe('Finalized');
    });

    test('labels resolve in both locales: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(StudentReportStatus::DRAFT->label())->toBe('Draft');
        expect(StudentReportStatus::FINALIZED->label())->toBe('Final');
    });
    test('finalized is the only terminal state', function (): void {
        expect(StudentReportStatus::FINALIZED->isTerminal())->toBeTrue();
        expect(StudentReportStatus::DRAFT->isTerminal())->toBeFalse();
    });

    test('sign-off moves draft to finalized and nothing else', function (): void {
        expect(StudentReportStatus::DRAFT->validTransitions())->toBe([StudentReportStatus::FINALIZED]);
        expect(StudentReportStatus::FINALIZED->validTransitions())->toBe([]);
        expect(StudentReportStatus::DRAFT->canTransitionTo(StudentReportStatus::FINALIZED))->toBeTrue();
        expect(StudentReportStatus::FINALIZED->canTransitionTo(StudentReportStatus::DRAFT))->toBeFalse();
        expect(StudentReportStatus::DRAFT->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
