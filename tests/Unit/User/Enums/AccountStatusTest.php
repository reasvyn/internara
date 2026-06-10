<?php

declare(strict_types=1);

use App\Core\Contracts\StatusEnum;
use App\User\Enums\AccountStatus;

describe('allowsLogin', function () {
    it('allows login for activated', function () {
        expect(AccountStatus::ACTIVATED->allowsLogin())->toBeTrue();
    });

    it('allows login for verified', function () {
        expect(AccountStatus::VERIFIED->allowsLogin())->toBeTrue();
    });

    it('allows login for protected', function () {
        expect(AccountStatus::PROTECTED->allowsLogin())->toBeTrue();
    });

    it('allows login for restricted', function () {
        expect(AccountStatus::RESTRICTED->allowsLogin())->toBeTrue();
    });

    it('allows login for inactive', function () {
        expect(AccountStatus::INACTIVE->allowsLogin())->toBeTrue();
    });

    it('blocks login for provisioned', function () {
        expect(AccountStatus::PROVISIONED->allowsLogin())->toBeFalse();
    });

    it('blocks login for suspended', function () {
        expect(AccountStatus::SUSPENDED->allowsLogin())->toBeFalse();
    });

    it('blocks login for archived', function () {
        expect(AccountStatus::ARCHIVED->allowsLogin())->toBeFalse();
    });
});

describe('isTerminal', function () {
    it('archived is terminal', function () {
        expect(AccountStatus::ARCHIVED->isTerminal())->toBeTrue();
    });

    it('protected is terminal', function () {
        expect(AccountStatus::PROTECTED->isTerminal())->toBeTrue();
    });

    it('activated is not terminal', function () {
        expect(AccountStatus::ACTIVATED->isTerminal())->toBeFalse();
    });
});

describe('transitions', function () {
    it('provisioned can transition to activated', function () {
        expect(AccountStatus::PROVISIONED->canTransitionTo(AccountStatus::ACTIVATED))->toBeTrue();
    });

    it('provisioned can transition to suspended', function () {
        expect(AccountStatus::PROVISIONED->canTransitionTo(AccountStatus::SUSPENDED))->toBeTrue();
    });

    it('provisioned cannot transition to archived', function () {
        expect(AccountStatus::PROVISIONED->canTransitionTo(AccountStatus::ARCHIVED))->toBeFalse();
    });

    it('protected has no transitions', function () {
        expect(AccountStatus::PROTECTED->validTransitions())->toBe([]);
        expect(AccountStatus::PROTECTED->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse();
    });

    it('archived has no transitions', function () {
        expect(AccountStatus::ARCHIVED->validTransitions())->toBe([]);
        expect(AccountStatus::ARCHIVED->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse();
    });

    it('activated can transition to verified', function () {
        expect(AccountStatus::ACTIVATED->canTransitionTo(AccountStatus::VERIFIED))->toBeTrue();
    });

    it('activated can transition to suspended', function () {
        expect(AccountStatus::ACTIVATED->canTransitionTo(AccountStatus::SUSPENDED))->toBeTrue();
    });

    it('activated can transition to archived', function () {
        expect(AccountStatus::ACTIVATED->canTransitionTo(AccountStatus::ARCHIVED))->toBeTrue();
    });

    it('returns false for wrong enum type', function () {
        $mock = new class implements StatusEnum
        {
            public function label(): string
            {
                return 'mock';
            }

            public function isTerminal(): bool
            {
                return false;
            }

            public function canTransitionTo(StatusEnum $target): bool
            {
                return false;
            }

            public function validTransitions(): array
            {
                return [];
            }
        };

        expect(AccountStatus::ACTIVATED->canTransitionTo($mock))->toBeFalse();
    });
});

describe('label', function () {
    it('returns translated label', function () {
        expect(AccountStatus::ACTIVATED->label())->toBeString();
    });
});

describe('color', function () {
    it('returns color for each status', function () {
        foreach (AccountStatus::cases() as $status) {
            expect($status->color())->toBeString();
        }
    });
});
