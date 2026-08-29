<?php

declare(strict_types=1);

use App\Modules\Core\Contracts\ColorableEnum;
use App\Modules\Core\Contracts\StatusEnum;
use App\Modules\User\Enums\AccountStatus;

/*
|--------------------------------------------------------------------------
| 95EVB — User CRUD and Status — AccountStatus (spec-driven)
|--------------------------------------------------------------------------
*/

describe('95EVB: AccountStatus', function (): void {
    test('95EVB-FR-AS1: implements StatusEnum and ColorableEnum contracts', function (): void {
        $ref = new ReflectionClass(AccountStatus::class);

        expect($ref->implementsInterface(StatusEnum::class))->toBeTrue()
            ->and($ref->implementsInterface(ColorableEnum::class))->toBeTrue()
            ->and($ref->isEnum())->toBeTrue();
    });

    test('95EVB-FR-AS2: defines 8 states', function (): void {
        $cases = AccountStatus::cases();

        expect($cases)->toHaveCount(8)
            ->and(array_map(fn ($c) => $c->value, $cases))->toBe([
                'provisioned', 'activated', 'verified', 'protected',
                'restricted', 'suspended', 'inactive', 'archived',
            ]);
    });

    test('95EVB-FR-AS3: allowsLogin() returns true for ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, INACTIVE', function (): void {
        expect(AccountStatus::ACTIVATED->allowsLogin())->toBeTrue()
            ->and(AccountStatus::VERIFIED->allowsLogin())->toBeTrue()
            ->and(AccountStatus::PROTECTED->allowsLogin())->toBeTrue()
            ->and(AccountStatus::RESTRICTED->allowsLogin())->toBeTrue()
            ->and(AccountStatus::INACTIVE->allowsLogin())->toBeTrue()
            ->and(AccountStatus::PROVISIONED->allowsLogin())->toBeFalse()
            ->and(AccountStatus::SUSPENDED->allowsLogin())->toBeFalse()
            ->and(AccountStatus::ARCHIVED->allowsLogin())->toBeFalse();
    });

    test('95EVB-FR-AS4: isTerminal() returns true for PROTECTED and ARCHIVED', function (): void {
        expect(AccountStatus::PROTECTED->isTerminal())->toBeTrue()
            ->and(AccountStatus::ARCHIVED->isTerminal())->toBeTrue()
            ->and(AccountStatus::VERIFIED->isTerminal())->toBeFalse()
            ->and(AccountStatus::PROVISIONED->isTerminal())->toBeFalse();
    });

    test('95EVB-FR-AS5: validTransitions() defines correct transitions', function (): void {
        expect(AccountStatus::PROVISIONED->validTransitions())->toBe([AccountStatus::ACTIVATED, AccountStatus::SUSPENDED])
            ->and(AccountStatus::ACTIVATED->validTransitions())->toBe([AccountStatus::VERIFIED, AccountStatus::SUSPENDED, AccountStatus::ARCHIVED])
            ->and(AccountStatus::VERIFIED->validTransitions())->toBe([AccountStatus::RESTRICTED, AccountStatus::SUSPENDED, AccountStatus::ARCHIVED, AccountStatus::INACTIVE])
            ->and(AccountStatus::PROTECTED->validTransitions())->toBe([])
            ->and(AccountStatus::RESTRICTED->validTransitions())->toBe([AccountStatus::VERIFIED, AccountStatus::SUSPENDED, AccountStatus::ARCHIVED])
            ->and(AccountStatus::SUSPENDED->validTransitions())->toBe([AccountStatus::ACTIVATED, AccountStatus::VERIFIED, AccountStatus::ARCHIVED])
            ->and(AccountStatus::INACTIVE->validTransitions())->toBe([AccountStatus::VERIFIED, AccountStatus::ARCHIVED, AccountStatus::SUSPENDED])
            ->and(AccountStatus::ARCHIVED->validTransitions())->toBe([]);
    });

    test('95EVB-FR-AS6: canTransitionTo() returns false for all transitions from terminal states', function (): void {
        expect(AccountStatus::PROTECTED->canTransitionTo(AccountStatus::VERIFIED))->toBeFalse()
            ->and(AccountStatus::ARCHIVED->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse()
            ->and(AccountStatus::PROTECTED->canTransitionTo(AccountStatus::ARCHIVED))->toBeFalse();
    });

    test('95EVB-FR-AS6: canTransitionTo() validates allowed transitions', function (): void {
        expect(AccountStatus::PROVISIONED->canTransitionTo(AccountStatus::ACTIVATED))->toBeTrue()
            ->and(AccountStatus::PROVISIONED->canTransitionTo(AccountStatus::VERIFIED))->toBeFalse()
            ->and(AccountStatus::VERIFIED->canTransitionTo(AccountStatus::RESTRICTED))->toBeTrue()
            ->and(AccountStatus::VERIFIED->canTransitionTo(AccountStatus::PROTECTED))->toBeFalse();
    });

    test('95EVB-FR-AS12: color() returns correct mapping', function (): void {
        expect(AccountStatus::PROVISIONED->color())->toBe('warning')
            ->and(AccountStatus::ACTIVATED->color())->toBe('info')
            ->and(AccountStatus::VERIFIED->color())->toBe('success')
            ->and(AccountStatus::PROTECTED->color())->toBe('primary')
            ->and(AccountStatus::RESTRICTED->color())->toBe('warning')
            ->and(AccountStatus::SUSPENDED->color())->toBe('error')
            ->and(AccountStatus::INACTIVE->color())->toBe('warning')
            ->and(AccountStatus::ARCHIVED->color())->toBe('error');
    });

    test('95EVB-FR-AS13: label() returns translated string via __()', function (): void {
        foreach (AccountStatus::cases() as $status) {
            $label = $status->label();
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });
});
