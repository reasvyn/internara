<?php

declare(strict_types=1);

use App\Domain\Assessment\Enums\PresentationStatus;
use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

describe('PresentationStatus enum', function () {
    it('implements LabelEnum', function () {
        expect(PresentationStatus::class)->toImplement(LabelEnum::class);
    });

    it('implements StatusEnum', function () {
        expect(PresentationStatus::class)->toImplement(StatusEnum::class);
    });

    it('has labels', function () {
        expect(PresentationStatus::SCHEDULED->label())->toBe('Scheduled')
            ->and(PresentationStatus::COMPLETED->label())->toBe('Completed')
            ->and(PresentationStatus::CANCELLED->label())->toBe('Cancelled');
    });

    it('detects terminal', function () {
        expect(PresentationStatus::COMPLETED->isTerminal())->toBeTrue()
            ->and(PresentationStatus::SCHEDULED->isTerminal())->toBeFalse();
    });

    it('validates transitions', function () {
        expect(PresentationStatus::SCHEDULED->canTransitionTo(PresentationStatus::COMPLETED))->toBeTrue()
            ->and(PresentationStatus::COMPLETED->canTransitionTo(PresentationStatus::SCHEDULED))->toBeFalse();
    });
});
