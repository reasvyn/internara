<?php

declare(strict_types=1);

use App\Modules\Settings\Data\SettingEntryData;
use App\Modules\Setup\Entities\SetupEntity;
use Carbon\Carbon;

describe('8NZAU: setup entity', function (): void {
    $attributes = fn (): array => [
        'dbInstalled' => false,
        'setupToken' => 'token-value',
        'tokenExpiresAt' => Carbon::parse('2026-05-01 13:00:00'),
        'completedSteps' => ['audit'],
        'recoveryKey' => 'recovery-value',
    ];

    test('8NZAU-FR-INST-011: fromArray hydrates install state with versioned defaults', function () use ($attributes): void {
        $entity = SetupEntity::fromArray($attributes());

        expect($entity->isInstalled())->toBeFalse();
        expect($entity->setupToken())->toBe('token-value');
        expect($entity->hasStoredToken())->toBeTrue();
        expect($entity->recoveryKey())->toBe('recovery-value');
        expect($entity->hasRecoveryKey())->toBeTrue();
        expect($entity->tokenVersion())->toBe(0);
        expect($entity->updatedAt())->toBeNull();
        expect($entity->completedSteps())->toBe(['audit']);
        expect($entity->tokenExpiresAt()?->equalTo(Carbon::parse('2026-05-01 13:00:00')))->toBeTrue();
    });

    test('8NZAU-FR-INST-011: isTokenExpired treats a missing expiry as expired', function () use ($attributes): void {
        $entity = SetupEntity::fromArray($attributes());

        expect($entity->isTokenExpired(Carbon::parse('2026-05-01 12:00:00')))->toBeFalse();
        expect($entity->isTokenExpired(Carbon::parse('2026-05-01 14:00:00')))->toBeTrue();

        $dateless = SetupEntity::fromArray([...$attributes(), 'setupToken' => null, 'tokenExpiresAt' => null, 'recoveryKey' => null, 'completedSteps' => []]);

        expect($dateless->isTokenExpired(Carbon::parse('2026-05-01 12:00:00')))->toBeTrue();
        expect($dateless->hasStoredToken())->toBeFalse();
        expect($dateless->hasRecoveryKey())->toBeFalse();
    });

    test('8NZAU-FR-INST-011: validateToken combines expiry with a constant-time comparison', function () use ($attributes): void {
        $entity = SetupEntity::fromArray($attributes());
        $now = Carbon::parse('2026-05-01 12:00:00');

        expect($entity->validateToken('token-value', 'token-value', $now))->toBeTrue();
        expect($entity->validateToken('token-value', 'wrong-value', $now))->toBeFalse();
        expect($entity->validateToken('token-value', 'token-value', Carbon::parse('2026-05-01 14:00:00')))->toBeFalse();
    });

    test('8NZAU-FR-INST-012: tokenVersion survives construction for session invalidation', function () use ($attributes): void {
        $entity = SetupEntity::fromArray([...$attributes(), 'tokenVersion' => 3]);

        expect($entity->tokenVersion())->toBe(3);
    });

    test('tracks completed wizard steps by key', function () use ($attributes): void {
        $entity = SetupEntity::fromArray($attributes());

        expect($entity->isStepCompleted('audit'))->toBeTrue();
        expect($entity->isStepCompleted('provision'))->toBeFalse();
    });

    test('allStepsCompleted answers the configured wizard when steps exist', function () use ($attributes): void {
        config()->set('setup.wizard.step_keys', ['audit', 'provision']);

        expect(SetupEntity::fromArray($attributes())->allStepsCompleted())->toBeFalse();
        expect(SetupEntity::fromArray([...$attributes(), 'completedSteps' => ['audit', 'provision']])->allStepsCompleted())->toBeTrue();

        config()->set('setup.wizard.step_keys', []);

        expect(SetupEntity::fromArray($attributes())->allStepsCompleted())->toBeTrue();
        expect(SetupEntity::fromArray([...$attributes(), 'completedSteps' => []])->allStepsCompleted())->toBeFalse();
    });

    test('8NZAU-FR-INST-022: finalization windows close after their configured span', function () use ($attributes): void {
        $justFinalized = SetupEntity::fromArray([...$attributes(), 'updatedAt' => Carbon::now()]);
        $older = SetupEntity::fromArray([...$attributes(), 'updatedAt' => Carbon::now()->subMinutes(10)]);
        $never = SetupEntity::fromArray($attributes());

        expect($justFinalized->isWithinFinalizationWindow())->toBeTrue();
        expect($justFinalized->isWithinFinalizationWindowSeconds())->toBeTrue();
        expect($older->isWithinFinalizationWindow())->toBeFalse();
        expect($older->isWithinFinalizationWindowSeconds())->toBeFalse();
        expect($never->isWithinFinalizationWindow())->toBeFalse();
        expect($never->isWithinFinalizationWindowSeconds())->toBeFalse();
    });

    test('8NZAU-FR-INST-011: keys exposes the seven setup setting keys', function (): void {
        expect(SetupEntity::keys())->toBe([
            'is_installed' => 'setup.is_installed',
            'install_token' => 'setup.install_token',
            'token_expires_at' => 'setup.token_expires_at',
            'completed_steps' => 'setup.completed_steps',
            'install_recovery_key' => 'setup.install_recovery_key',
            'token_version' => 'setup.token_version',
            'updated_at' => 'setup.updated_at',
        ]);
    });

    test('maps attributes to typed setting entries for persistence', function (): void {
        $entries = SetupEntity::toSettingsEntries([
            'is_installed' => true,
            'completed_steps' => ['audit'],
            'token_version' => 2,
            'custom_flag' => false,
            'custom_list' => ['a'],
            'custom_count' => 7,
            'custom_note' => 'hi',
        ]);

        expect($entries)->toHaveCount(7);
        expect($entries[0])->toBeInstanceOf(SettingEntryData::class);

        $byKey = [];
        foreach ($entries as $entry) {
            $byKey[$entry->key] = $entry->type;
        }

        expect($byKey)->toBe([
            'setup.is_installed' => 'boolean',
            'setup.completed_steps' => 'json',
            'setup.token_version' => 'integer',
            'setup.custom_flag' => 'boolean',
            'setup.custom_list' => 'json',
            'setup.custom_count' => 'integer',
            'setup.custom_note' => 'string',
        ]);
        expect($entries[0]->group)->toBe('setup');
        expect($entries[0]->value)->toBeTrue();
    });

    test('8NZAU-FR-INST-011: fromArray rejects a missing install flag', function (): void {
        expect(fn (): SetupEntity => SetupEntity::fromArray([
            'setupToken' => null,
            'tokenExpiresAt' => null,
            'completedSteps' => [],
            'recoveryKey' => null,
        ]))->toThrow(InvalidArgumentException::class, 'dbInstalled');
    });

    test('8NZAU-FR-INST-011: equals compares install snapshots by value', function () use ($attributes): void {
        $entity = SetupEntity::fromArray($attributes());

        expect($entity->equals(SetupEntity::fromArray($attributes())))->toBeTrue();
        expect($entity->equals(SetupEntity::fromArray([...$attributes(), 'dbInstalled' => true])))->toBeFalse();
    });
});
