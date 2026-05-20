<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Core\Contracts\StatusEnum;

describe('AccountStatus', function () {
    it('is string-backed', function () {
        expect(AccountStatus::PROVISIONED->value)->toBe('provisioned');
    });

    it('implements StatusEnum', function () {
        expect(AccountStatus::PROVISIONED)->toBeInstanceOf(StatusEnum::class);
    });

    it('allows login for active states', function () {
        expect(AccountStatus::ACTIVATED->allowsLogin())->toBeTrue()
            ->and(AccountStatus::VERIFIED->allowsLogin())->toBeTrue()
            ->and(AccountStatus::PROTECTED->allowsLogin())->toBeTrue();
    });

    it('blocks login for suspended and archived', function () {
        expect(AccountStatus::SUSPENDED->allowsLogin())->toBeFalse()
            ->and(AccountStatus::ARCHIVED->allowsLogin())->toBeFalse()
            ->and(AccountStatus::PROVISIONED->allowsLogin())->toBeFalse();
    });

    it('identifies terminal states', function () {
        expect(AccountStatus::ARCHIVED->isTerminal())->toBeTrue()
            ->and(AccountStatus::PROTECTED->isTerminal())->toBeTrue()
            ->and(AccountStatus::ACTIVATED->isTerminal())->toBeFalse();
    });

    it('validates transitions correctly', function () {
        expect(AccountStatus::PROVISIONED->canTransitionTo(AccountStatus::ACTIVATED))->toBeTrue()
            ->and(AccountStatus::PROVISIONED->canTransitionTo(AccountStatus::VERIFIED))->toBeFalse()
            ->and(AccountStatus::PROTECTED->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse();
    });

    it('terminal states have empty transitions', function () {
        expect(AccountStatus::ARCHIVED->validTransitions())->toBe([])
            ->and(AccountStatus::PROTECTED->validTransitions())->toBe([]);
    });
});
