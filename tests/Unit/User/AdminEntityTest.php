<?php

declare(strict_types=1);

use App\Modules\User\Entities\AdminEntity;
use App\Modules\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;

final class AdminEntityModelDouble extends Model
{
    protected $guarded = [];
}

describe('95EVB: admin entity', function (): void {
    test('95EVB-FR-USER-019: fromModel bridges status, lock, and setup flags without persisting', function (): void {
        $model = new AdminEntityModelDouble([
            'status' => AccountStatus::VERIFIED,
            'locked_at' => null,
            'setup_required' => false,
        ]);

        $entity = AdminEntity::fromModel($model);

        expect($entity->status())->toBe(AccountStatus::VERIFIED);
        expect($entity->isLocked())->toBeFalse();
        expect($entity->requiresSetup())->toBeFalse();
        expect($entity->isSuspended())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('95EVB-FR-USER-019: fromModel accepts raw status strings and falls back to provisioned', function (): void {
        $suspended = new AdminEntityModelDouble(['status' => 'suspended', 'locked_at' => '2026-01-01 00:00:00', 'setup_required' => true]);
        $unknown = new AdminEntityModelDouble(['status' => 'not-a-status', 'locked_at' => null, 'setup_required' => false]);

        expect(AdminEntity::fromModel($suspended)->isSuspended())->toBeTrue();
        expect(AdminEntity::fromModel($suspended)->isLocked())->toBeTrue();
        expect(AdminEntity::fromModel($suspended)->requiresSetup())->toBeTrue();
        expect(AdminEntity::fromModel($unknown)->status())->toBe(AccountStatus::PROVISIONED);
        expect($suspended->exists)->toBeFalse();
    });

    test('95EVB-FR-USER-015: suspension, archive, and inactivity answer the stored status', function (): void {
        $archived = AdminEntity::fromArray(['status' => AccountStatus::ARCHIVED, 'isLocked' => false, 'setupRequired' => false]);
        $inactive = AdminEntity::fromArray(['status' => AccountStatus::INACTIVE, 'isLocked' => false, 'setupRequired' => false]);
        $verified = AdminEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);

        expect($archived->isArchived())->toBeTrue();
        expect($archived->isSuspended())->toBeFalse();
        expect($inactive->isInactive())->toBeTrue();
        expect($inactive->isArchived())->toBeFalse();
        expect($verified->isSuspended())->toBeFalse();
        expect($verified->isArchived())->toBeFalse();
        expect($verified->isInactive())->toBeFalse();
    });

    test('95EVB-FR-USER-019: canTransitionTo delegates to the status state machine', function (): void {
        $verified = AdminEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);
        $archived = AdminEntity::fromArray(['status' => AccountStatus::ARCHIVED, 'isLocked' => false, 'setupRequired' => false]);

        expect($verified->canTransitionTo(AccountStatus::SUSPENDED))->toBeTrue();
        expect($verified->canTransitionTo(AccountStatus::PROTECTED))->toBeFalse();
        expect($archived->canTransitionTo(AccountStatus::VERIFIED))->toBeFalse();
    });

    test('95EVB-FR-USER-019: fromArray rejects a missing status', function (): void {
        expect(fn (): AdminEntity => AdminEntity::fromArray(['isLocked' => false, 'setupRequired' => false]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('95EVB-FR-USER-019: toArray, equals, and with round-trip by value', function (): void {
        $entity = AdminEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);

        expect($entity->toArray())->toBe(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);
        expect($entity->equals(AdminEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false])))->toBeTrue();

        $suspended = $entity->with('status', AccountStatus::SUSPENDED);

        expect($suspended->isSuspended())->toBeTrue();
        expect($entity->isSuspended())->toBeFalse();
    });
});
