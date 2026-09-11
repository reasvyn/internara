<?php

declare(strict_types=1);

use App\Modules\User\Entities\Apprentice;
use App\Modules\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;

final class ApprenticeModelDouble extends Model
{
    protected $guarded = [];
}

describe('YB7RG: apprentice entity', function (): void {
    test('YB7RG-FR-AUTH-004: fromModel bridges the login gate snapshot without persisting', function (): void {
        $model = new ApprenticeModelDouble([
            'status' => AccountStatus::ACTIVATED,
            'locked_at' => null,
            'setup_required' => false,
        ]);

        $entity = Apprentice::fromModel($model);

        expect($entity->status()->allowsLogin())->toBeTrue();
        expect($entity->isLocked())->toBeFalse();
        expect($entity->requiresSetup())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-006: fromModel reports lock and setup flags from raw attributes', function (): void {
        $model = new ApprenticeModelDouble([
            'status' => 'provisioned',
            'locked_at' => '2026-01-01 00:00:00',
            'setup_required' => true,
        ]);

        $entity = Apprentice::fromModel($model);

        expect($entity->status()->allowsLogin())->toBeFalse();
        expect($entity->isLocked())->toBeTrue();
        expect($entity->requiresSetup())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('YB7RG-FR-AUTH-005: suspended, archived, and inactive answer the stored status', function (): void {
        $suspended = Apprentice::fromArray(['status' => AccountStatus::SUSPENDED, 'isLocked' => false, 'setupRequired' => false]);
        $archived = Apprentice::fromArray(['status' => AccountStatus::ARCHIVED, 'isLocked' => false, 'setupRequired' => false]);
        $inactive = Apprentice::fromArray(['status' => AccountStatus::INACTIVE, 'isLocked' => false, 'setupRequired' => false]);

        expect($suspended->isSuspended())->toBeTrue();
        expect($suspended->status()->allowsLogin())->toBeFalse();
        expect($archived->isArchived())->toBeTrue();
        expect($archived->status()->allowsLogin())->toBeFalse();
        expect($inactive->isInactive())->toBeTrue();
        expect($inactive->status()->allowsLogin())->toBeTrue();
    });

    test('95EVB-FR-USER-019: canTransitionTo delegates to the status state machine', function (): void {
        $provisioned = Apprentice::fromArray(['status' => AccountStatus::PROVISIONED, 'isLocked' => false, 'setupRequired' => false]);

        expect($provisioned->canTransitionTo(AccountStatus::ACTIVATED))->toBeTrue();
        expect($provisioned->canTransitionTo(AccountStatus::ARCHIVED))->toBeFalse();
    });

    test('YB7RG-FR-AUTH-007: fromArray rejects a missing status', function (): void {
        expect(fn (): Apprentice => Apprentice::fromArray(['isLocked' => false, 'setupRequired' => false]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('YB7RG-FR-AUTH-004: toArray, equals, and with round-trip by value', function (): void {
        $entity = Apprentice::fromArray(['status' => AccountStatus::ACTIVATED, 'isLocked' => false, 'setupRequired' => true]);

        expect($entity->toArray())->toBe(['status' => AccountStatus::ACTIVATED, 'isLocked' => false, 'setupRequired' => true]);
        expect($entity->requiresSetup())->toBeTrue();

        $ready = $entity->with('setupRequired', false);

        expect($ready->requiresSetup())->toBeFalse();
        expect($entity->requiresSetup())->toBeTrue();
    });
});
