<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\StatusEnum;
use App\Domain\Mentor\Enums\SupervisionLogStatus;

describe('SupervisionLogStatus', function () {
    it('is string-backed', function () {
        expect(SupervisionLogStatus::PENDING->value)->toBe('pending');
    });

    it('implements StatusEnum', function () {
        expect(SupervisionLogStatus::PENDING)->toBeInstanceOf(StatusEnum::class);
    });

    it('identifies active states', function () {
        expect(SupervisionLogStatus::PENDING->isActive())->toBeTrue()
            ->and(SupervisionLogStatus::COMPLETED->isActive())->toBeFalse();
    });

    it('identifies terminal states', function () {
        expect(SupervisionLogStatus::COMPLETED->isTerminal())->toBeTrue()
            ->and(SupervisionLogStatus::VERIFIED->isTerminal())->toBeTrue()
            ->and(SupervisionLogStatus::PENDING->isTerminal())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(SupervisionLogStatus::PENDING->canTransitionTo(SupervisionLogStatus::IN_PROGRESS))->toBeTrue()
            ->and(SupervisionLogStatus::COMPLETED->canTransitionTo(SupervisionLogStatus::PENDING))->toBeFalse();
    });
});
