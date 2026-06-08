<?php

declare(strict_types=1);

use App\Program\Internship\Enums\InternshipStatus;

describe('isAcceptingRegistrations', function () {
    it('published accepts registrations', function () {
        expect(InternshipStatus::PUBLISHED->isAcceptingRegistrations())->toBeTrue();
    });

    it('active accepts registrations', function () {
        expect(InternshipStatus::ACTIVE->isAcceptingRegistrations())->toBeTrue();
    });

    it('draft does not accept registrations', function () {
        expect(InternshipStatus::DRAFT->isAcceptingRegistrations())->toBeFalse();
    });

    it('completed does not accept registrations', function () {
        expect(InternshipStatus::COMPLETED->isAcceptingRegistrations())->toBeFalse();
    });

    it('cancelled does not accept registrations', function () {
        expect(InternshipStatus::CANCELLED->isAcceptingRegistrations())->toBeFalse();
    });
});

describe('isTerminal', function () {
    it('completed is terminal', function () {
        expect(InternshipStatus::COMPLETED->isTerminal())->toBeTrue();
    });

    it('cancelled is terminal', function () {
        expect(InternshipStatus::CANCELLED->isTerminal())->toBeTrue();
    });

    it('draft is not terminal', function () {
        expect(InternshipStatus::DRAFT->isTerminal())->toBeFalse();
    });
});

describe('transitions', function () {
    it('draft can transition to published', function () {
        expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::PUBLISHED))->toBeTrue();
    });

    it('draft can transition to cancelled', function () {
        expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::CANCELLED))->toBeTrue();
    });

    it('published can transition to active', function () {
        expect(InternshipStatus::PUBLISHED->canTransitionTo(InternshipStatus::ACTIVE))->toBeTrue();
    });

    it('active can transition to completed', function () {
        expect(InternshipStatus::ACTIVE->canTransitionTo(InternshipStatus::COMPLETED))->toBeTrue();
    });

    it('completed has no transitions', function () {
        expect(InternshipStatus::COMPLETED->validTransitions())->toBe([]);
    });

    it('rejects non-self type', function () {
        $mock = new class implements \App\Core\Contracts\StatusEnum {
            public function label(): string { return 'mock'; }
            public function isTerminal(): bool { return false; }
            public function canTransitionTo(\App\Core\Contracts\StatusEnum $t): bool { return false; }
            public function validTransitions(): array { return []; }
        };

        expect(InternshipStatus::DRAFT->canTransitionTo($mock))->toBeFalse();
    });
});

describe('label', function () {
    it('returns label for each status', function () {
        foreach (InternshipStatus::cases() as $status) {
            expect($status->label())->toBeString();
        }
    });
});