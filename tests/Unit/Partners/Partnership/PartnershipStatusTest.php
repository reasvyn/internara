<?php

declare(strict_types=1);

use App\Partners\Partnership\Enums\PartnershipStatus;

describe('isTerminal', function () {
    it('expired is terminal', function () {
        expect(PartnershipStatus::EXPIRED->isTerminal())->toBeTrue();
    });

    it('terminated is terminal', function () {
        expect(PartnershipStatus::TERMINATED->isTerminal())->toBeTrue();
    });

    it('active is not terminal', function () {
        expect(PartnershipStatus::ACTIVE->isTerminal())->toBeFalse();
    });
});

describe('transitions', function () {
    it('active can transition to expired', function () {
        expect(PartnershipStatus::ACTIVE->canTransitionTo(PartnershipStatus::EXPIRED))->toBeTrue();
    });

    it('active can transition to terminated', function () {
        expect(PartnershipStatus::ACTIVE->canTransitionTo(PartnershipStatus::TERMINATED))->toBeTrue();
    });

    it('expired cannot transition', function () {
        expect(PartnershipStatus::EXPIRED->validTransitions())->toBe([]);
    });

    it('terminated cannot transition', function () {
        expect(PartnershipStatus::TERMINATED->validTransitions())->toBe([]);
    });

    it('rejects non-self type', function () {
        $mock = new class implements \App\Core\Contracts\StatusEnum {
            public function label(): string { return 'mock'; }
            public function isTerminal(): bool { return false; }
            public function canTransitionTo(\App\Core\Contracts\StatusEnum $target): bool { return false; }
            public function validTransitions(): array { return []; }
        };

        expect(PartnershipStatus::ACTIVE->canTransitionTo($mock))->toBeFalse();
    });
});

describe('label', function () {
    it('returns label for each status', function () {
        foreach (PartnershipStatus::cases() as $status) {
            expect($status->label())->toBeString();
        }
    });
});