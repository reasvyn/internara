<?php

declare(strict_types=1);

use App\Enums\Internship\InternshipStatus;

describe('InternshipStatus', function () {

    describe('label', function () {
        it('returns correct label for draft', function () {
            expect(InternshipStatus::DRAFT->label())->toBe('Draft');
        });

        it('returns correct label for published', function () {
            expect(InternshipStatus::PUBLISHED->label())->toBe('Published');
        });

        it('returns correct label for active', function () {
            expect(InternshipStatus::ACTIVE->label())->toBe('Active');
        });

        it('returns correct label for completed', function () {
            expect(InternshipStatus::COMPLETED->label())->toBe('Completed');
        });

        it('returns correct label for cancelled', function () {
            expect(InternshipStatus::CANCELLED->label())->toBe('Cancelled');
        });
    });

    describe('isTerminal', function () {
        it('returns true for completed', function () {
            expect(InternshipStatus::COMPLETED->isTerminal())->toBeTrue();
        });

        it('returns true for cancelled', function () {
            expect(InternshipStatus::CANCELLED->isTerminal())->toBeTrue();
        });

        it('returns false for draft', function () {
            expect(InternshipStatus::DRAFT->isTerminal())->toBeFalse();
        });

        it('returns false for published', function () {
            expect(InternshipStatus::PUBLISHED->isTerminal())->toBeFalse();
        });

        it('returns false for active', function () {
            expect(InternshipStatus::ACTIVE->isTerminal())->toBeFalse();
        });
    });

    describe('isAcceptingRegistrations', function () {
        it('returns true for published', function () {
            expect(InternshipStatus::PUBLISHED->isAcceptingRegistrations())->toBeTrue();
        });

        it('returns true for active', function () {
            expect(InternshipStatus::ACTIVE->isAcceptingRegistrations())->toBeTrue();
        });

        it('returns false for draft', function () {
            expect(InternshipStatus::DRAFT->isAcceptingRegistrations())->toBeFalse();
        });

        it('returns false for completed', function () {
            expect(InternshipStatus::COMPLETED->isAcceptingRegistrations())->toBeFalse();
        });

        it('returns false for cancelled', function () {
            expect(InternshipStatus::CANCELLED->isAcceptingRegistrations())->toBeFalse();
        });
    });

    describe('validTransitions', function () {
        it('draft can transition to published and cancelled', function () {
            expect(InternshipStatus::DRAFT->validTransitions())->toBe([
                InternshipStatus::PUBLISHED,
                InternshipStatus::CANCELLED,
            ]);
        });

        it('published can transition to active and cancelled', function () {
            expect(InternshipStatus::PUBLISHED->validTransitions())->toBe([
                InternshipStatus::ACTIVE,
                InternshipStatus::CANCELLED,
            ]);
        });

        it('active can transition to completed and cancelled', function () {
            expect(InternshipStatus::ACTIVE->validTransitions())->toBe([
                InternshipStatus::COMPLETED,
                InternshipStatus::CANCELLED,
            ]);
        });

        it('completed has no valid transitions', function () {
            expect(InternshipStatus::COMPLETED->validTransitions())->toBe([]);
        });

        it('cancelled has no valid transitions', function () {
            expect(InternshipStatus::CANCELLED->validTransitions())->toBe([]);
        });
    });

    describe('canTransitionTo', function () {
        it('draft can transition to published', function () {
            expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::PUBLISHED))->toBeTrue();
        });

        it('draft can transition to cancelled', function () {
            expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::CANCELLED))->toBeTrue();
        });

        it('draft cannot transition to active', function () {
            expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::ACTIVE))->toBeFalse();
        });

        it('draft cannot transition to completed', function () {
            expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::COMPLETED))->toBeFalse();
        });

        it('published can transition to active', function () {
            expect(InternshipStatus::PUBLISHED->canTransitionTo(InternshipStatus::ACTIVE))->toBeTrue();
        });

        it('published can transition to cancelled', function () {
            expect(InternshipStatus::PUBLISHED->canTransitionTo(InternshipStatus::CANCELLED))->toBeTrue();
        });

        it('published cannot transition to draft', function () {
            expect(InternshipStatus::PUBLISHED->canTransitionTo(InternshipStatus::DRAFT))->toBeFalse();
        });

        it('published cannot transition to completed', function () {
            expect(InternshipStatus::PUBLISHED->canTransitionTo(InternshipStatus::COMPLETED))->toBeFalse();
        });

        it('active can transition to completed', function () {
            expect(InternshipStatus::ACTIVE->canTransitionTo(InternshipStatus::COMPLETED))->toBeTrue();
        });

        it('active can transition to cancelled', function () {
            expect(InternshipStatus::ACTIVE->canTransitionTo(InternshipStatus::CANCELLED))->toBeTrue();
        });

        it('active cannot transition to draft', function () {
            expect(InternshipStatus::ACTIVE->canTransitionTo(InternshipStatus::DRAFT))->toBeFalse();
        });

        it('active cannot transition to published', function () {
            expect(InternshipStatus::ACTIVE->canTransitionTo(InternshipStatus::PUBLISHED))->toBeFalse();
        });

        it('completed cannot transition to any status', function () {
            foreach (InternshipStatus::cases() as $target) {
                expect(InternshipStatus::COMPLETED->canTransitionTo($target))->toBeFalse();
            }
        });

        it('cancelled cannot transition to any status', function () {
            foreach (InternshipStatus::cases() as $target) {
                expect(InternshipStatus::CANCELLED->canTransitionTo($target))->toBeFalse();
            }
        });
    });

});
