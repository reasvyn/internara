<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Internship\Enums\InternshipStatus;

describe('InternshipStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(InternshipStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(InternshipStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(InternshipStatus::DRAFT->label())->toBe('Draft')
            ->and(InternshipStatus::PUBLISHED->label())->toBe('Published')
            ->and(InternshipStatus::ACTIVE->label())->toBe('Active')
            ->and(InternshipStatus::COMPLETED->label())->toBe('Completed')
            ->and(InternshipStatus::CANCELLED->label())->toBe('Cancelled');
    });

    it('detects accepting registrations', function () {
        expect(InternshipStatus::PUBLISHED->isAcceptingRegistrations())->toBeTrue()
            ->and(InternshipStatus::ACTIVE->isAcceptingRegistrations())->toBeTrue()
            ->and(InternshipStatus::DRAFT->isAcceptingRegistrations())->toBeFalse()
            ->and(InternshipStatus::COMPLETED->isAcceptingRegistrations())->toBeFalse();
    });

    it('detects terminal states', function () {
        expect(InternshipStatus::COMPLETED->isTerminal())->toBeTrue()
            ->and(InternshipStatus::CANCELLED->isTerminal())->toBeTrue()
            ->and(InternshipStatus::DRAFT->isTerminal())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::PUBLISHED))->toBeTrue()
            ->and(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::CANCELLED))->toBeTrue()
            ->and(InternshipStatus::DRAFT->canTransitionTo(InternshipStatus::ACTIVE))->toBeFalse()
            ->and(InternshipStatus::PUBLISHED->canTransitionTo(InternshipStatus::ACTIVE))->toBeTrue()
            ->and(InternshipStatus::ACTIVE->canTransitionTo(InternshipStatus::COMPLETED))->toBeTrue()
            ->and(InternshipStatus::COMPLETED->canTransitionTo(InternshipStatus::DRAFT))->toBeFalse();
    });
});
