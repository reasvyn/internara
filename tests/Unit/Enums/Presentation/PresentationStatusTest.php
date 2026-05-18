<?php

declare(strict_types=1);

use App\Enums\Presentation\PresentationStatus;

describe('PresentationStatus', function () {
    it('returns correct labels', function () {
        expect(PresentationStatus::SCHEDULED->label())->toBe('Scheduled');
        expect(PresentationStatus::COMPLETED->label())->toBe('Completed');
        expect(PresentationStatus::CANCELLED->label())->toBe('Cancelled');
    });

    it('completed and cancelled are terminal', function () {
        expect(PresentationStatus::COMPLETED->isTerminal())->toBeTrue();
        expect(PresentationStatus::CANCELLED->isTerminal())->toBeTrue();
        expect(PresentationStatus::SCHEDULED->isTerminal())->toBeFalse();
    });
});
