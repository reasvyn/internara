<?php

declare(strict_types=1);

use App\Enums\Report\ReportStatus;

describe('ReportStatus', function () {
    describe('label', function () {
        it('returns correct labels', function () {
            expect(ReportStatus::DRAFT->label())->toBe('Draft');
            expect(ReportStatus::SUBMITTED->label())->toBe('Submitted');
            expect(ReportStatus::REVISION_REQUIRED->label())->toBe('Revision Required');
            expect(ReportStatus::APPROVED->label())->toBe('Approved');
        });
    });

    describe('isTerminal', function () {
        it('approved is terminal', function () {
            expect(ReportStatus::APPROVED->isTerminal())->toBeTrue();
        });

        it('draft is not terminal', function () {
            expect(ReportStatus::DRAFT->isTerminal())->toBeFalse();
        });
    });

    describe('canTransitionTo', function () {
        it('draft can submit', function () {
            expect(ReportStatus::DRAFT->canTransitionTo(ReportStatus::SUBMITTED))->toBeTrue();
        });

        it('draft cannot approve directly', function () {
            expect(ReportStatus::DRAFT->canTransitionTo(ReportStatus::APPROVED))->toBeFalse();
        });

        it('submitted can be approved or revised', function () {
            expect(ReportStatus::SUBMITTED->canTransitionTo(ReportStatus::APPROVED))->toBeTrue();
            expect(ReportStatus::SUBMITTED->canTransitionTo(ReportStatus::REVISION_REQUIRED))->toBeTrue();
        });

        it('revision required can go back to draft', function () {
            expect(ReportStatus::REVISION_REQUIRED->canTransitionTo(ReportStatus::DRAFT))->toBeTrue();
        });

        it('approved has no transitions', function () {
            foreach (ReportStatus::cases() as $target) {
                expect(ReportStatus::APPROVED->canTransitionTo($target))->toBeFalse();
            }
        });
    });
});
