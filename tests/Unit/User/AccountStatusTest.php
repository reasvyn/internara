<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Core\Contracts\ColorableEnum;
use App\Modules\Core\Contracts\StatusEnum;
use App\Modules\User\Enums\AccountStatus;

describe('95EVB: AccountStatus enum', function (): void {
    test('95EVB-FR-USER-014: cases carry the specified backing values', function (): void {
        expect(AccountStatus::PROVISIONED->value)->toBe('provisioned');
        expect(AccountStatus::from('provisioned'))->toBe(AccountStatus::PROVISIONED);
        expect(AccountStatus::ACTIVATED->value)->toBe('activated');
        expect(AccountStatus::from('activated'))->toBe(AccountStatus::ACTIVATED);
        expect(AccountStatus::VERIFIED->value)->toBe('verified');
        expect(AccountStatus::from('verified'))->toBe(AccountStatus::VERIFIED);
        expect(AccountStatus::PROTECTED->value)->toBe('protected');
        expect(AccountStatus::from('protected'))->toBe(AccountStatus::PROTECTED);
        expect(AccountStatus::RESTRICTED->value)->toBe('restricted');
        expect(AccountStatus::from('restricted'))->toBe(AccountStatus::RESTRICTED);
        expect(AccountStatus::SUSPENDED->value)->toBe('suspended');
        expect(AccountStatus::from('suspended'))->toBe(AccountStatus::SUSPENDED);
        expect(AccountStatus::INACTIVE->value)->toBe('inactive');
        expect(AccountStatus::from('inactive'))->toBe(AccountStatus::INACTIVE);
        expect(AccountStatus::ARCHIVED->value)->toBe('archived');
        expect(AccountStatus::from('archived'))->toBe(AccountStatus::ARCHIVED);
        expect(AccountStatus::cases())->toHaveCount(8);
        expect(AccountStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('95EVB-FR-USER-026: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AccountStatus::PROVISIONED->label())->toBe('Provisioned');
        expect(AccountStatus::ACTIVATED->label())->toBe('Activated');
        expect(AccountStatus::VERIFIED->label())->toBe('Verified');
        expect(AccountStatus::PROTECTED->label())->toBe('Protected');
        expect(AccountStatus::RESTRICTED->label())->toBe('Restricted');
        expect(AccountStatus::SUSPENDED->label())->toBe('Suspended');
        expect(AccountStatus::INACTIVE->label())->toBe('Inactive');
        expect(AccountStatus::ARCHIVED->label())->toBe('Archived');
    });

    test('95EVB-FR-USER-026: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AccountStatus::PROVISIONED->label())->toBe('Tersedia');
        expect(AccountStatus::ACTIVATED->label())->toBe('Diaktifkan');
        expect(AccountStatus::VERIFIED->label())->toBe('Terverifikasi');
        expect(AccountStatus::PROTECTED->label())->toBe('Dilindungi');
        expect(AccountStatus::RESTRICTED->label())->toBe('Dibatasi');
        expect(AccountStatus::SUSPENDED->label())->toBe('Ditangguhkan');
        expect(AccountStatus::INACTIVE->label())->toBe('Nonaktif');
        expect(AccountStatus::ARCHIVED->label())->toBe('Diarsipkan');
    });
    test('95EVB-FR-USER-013: the enum signs the status and colorable contracts', function (): void {
        expect(AccountStatus::PROVISIONED)->toBeInstanceOf(StatusEnum::class);
        expect(AccountStatus::PROVISIONED)->toBeInstanceOf(ColorableEnum::class);
        expect(AccountStatus::cases())->toHaveCount(8);
    });

    test('YB7RG-FR-AUTH-005: provisioned, suspended, and archived cannot log in', function (): void {
        expect(AccountStatus::PROVISIONED->allowsLogin())->toBeFalse();
        expect(AccountStatus::SUSPENDED->allowsLogin())->toBeFalse();
        expect(AccountStatus::ARCHIVED->allowsLogin())->toBeFalse();
        expect(AccountStatus::ACTIVATED->allowsLogin())->toBeTrue();
        expect(AccountStatus::VERIFIED->allowsLogin())->toBeTrue();
        expect(AccountStatus::PROTECTED->allowsLogin())->toBeTrue();
        expect(AccountStatus::RESTRICTED->allowsLogin())->toBeTrue();
        expect(AccountStatus::INACTIVE->allowsLogin())->toBeTrue();
    });

    test('95EVB-FR-USER-016: protected and archived are terminal and nothing else is', function (): void {
        expect(AccountStatus::PROTECTED->isTerminal())->toBeTrue();
        expect(AccountStatus::ARCHIVED->isTerminal())->toBeTrue();
        expect(AccountStatus::PROVISIONED->isTerminal())->toBeFalse();
        expect(AccountStatus::ACTIVATED->isTerminal())->toBeFalse();
        expect(AccountStatus::VERIFIED->isTerminal())->toBeFalse();
        expect(AccountStatus::RESTRICTED->isTerminal())->toBeFalse();
        expect(AccountStatus::SUSPENDED->isTerminal())->toBeFalse();
        expect(AccountStatus::INACTIVE->isTerminal())->toBeFalse();
    });

    test('95EVB-FR-USER-017: validTransitions pins the full state map', function (): void {
        expect(AccountStatus::PROVISIONED->validTransitions())->toBe([AccountStatus::ACTIVATED, AccountStatus::SUSPENDED]);
        expect(AccountStatus::ACTIVATED->validTransitions())->toBe([AccountStatus::VERIFIED, AccountStatus::SUSPENDED, AccountStatus::ARCHIVED]);
        expect(AccountStatus::VERIFIED->validTransitions())->toBe([AccountStatus::RESTRICTED, AccountStatus::SUSPENDED, AccountStatus::ARCHIVED, AccountStatus::INACTIVE]);
        expect(AccountStatus::PROTECTED->validTransitions())->toBe([]);
        expect(AccountStatus::RESTRICTED->validTransitions())->toBe([AccountStatus::VERIFIED, AccountStatus::SUSPENDED, AccountStatus::ARCHIVED]);
        expect(AccountStatus::SUSPENDED->validTransitions())->toBe([AccountStatus::ACTIVATED, AccountStatus::VERIFIED, AccountStatus::ARCHIVED]);
        expect(AccountStatus::INACTIVE->validTransitions())->toBe([AccountStatus::VERIFIED, AccountStatus::ARCHIVED, AccountStatus::SUSPENDED]);
        expect(AccountStatus::ARCHIVED->validTransitions())->toBe([]);
    });

    test('95EVB-FR-USER-018: no transition leaves a terminal state', function (): void {
        expect(AccountStatus::PROTECTED->canTransitionTo(AccountStatus::VERIFIED))->toBeFalse();
        expect(AccountStatus::ARCHIVED->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse();
        expect(AccountStatus::VERIFIED->canTransitionTo(AccountStatus::RESTRICTED))->toBeTrue();
        expect(AccountStatus::SUSPENDED->canTransitionTo(AccountStatus::VERIFIED))->toBeTrue();
        expect(AccountStatus::VERIFIED->canTransitionTo(AccountStatus::PROTECTED))->toBeFalse();
        expect(AccountStatus::VERIFIED->canTransitionTo(AssignmentStatus::PUBLISHED))->toBeFalse();
    });

    test('95EVB-FR-USER-025: color pairs each state with its badge tone', function (): void {
        expect(AccountStatus::PROVISIONED->color())->toBe('warning');
        expect(AccountStatus::ACTIVATED->color())->toBe('info');
        expect(AccountStatus::VERIFIED->color())->toBe('success');
        expect(AccountStatus::PROTECTED->color())->toBe('primary');
        expect(AccountStatus::RESTRICTED->color())->toBe('warning');
        expect(AccountStatus::SUSPENDED->color())->toBe('error');
        expect(AccountStatus::INACTIVE->color())->toBe('warning');
        expect(AccountStatus::ARCHIVED->color())->toBe('error');
    });
});
