<?php

declare(strict_types=1);

use App\Modules\User\Entities\SupervisorEntity;
use App\Modules\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;

final class SupervisorEntityModelDouble extends Model
{
    protected $guarded = [];
}

describe('95EVB: supervisor entity', function (): void {
    test('95EVB-FR-USER-019: fromModel bridges the account snapshot without persisting', function (): void {
        $model = new SupervisorEntityModelDouble([
            'status' => AccountStatus::VERIFIED,
            'locked_at' => null,
            'setup_required' => false,
        ]);

        $entity = SupervisorEntity::fromModel($model);

        expect($entity->status())->toBe(AccountStatus::VERIFIED);
        expect($entity->isLocked())->toBeFalse();
        expect($entity->requiresSetup())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('95EVB-FR-USER-015: suspension, archive, and inactivity answer the stored status', function (): void {
        $base = ['isLocked' => false, 'setupRequired' => false];

        expect(SupervisorEntity::fromArray([...$base, 'status' => AccountStatus::SUSPENDED])->isSuspended())->toBeTrue();
        expect(SupervisorEntity::fromArray([...$base, 'status' => AccountStatus::ARCHIVED])->isArchived())->toBeTrue();
        expect(SupervisorEntity::fromArray([...$base, 'status' => AccountStatus::INACTIVE])->isInactive())->toBeTrue();
        expect(SupervisorEntity::fromArray([...$base, 'status' => AccountStatus::VERIFIED])->isInactive())->toBeFalse();
    });

    test('exposes company and mentorship linkage with empty defaults', function (): void {
        $plain = SupervisorEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);
        $linked = SupervisorEntity::fromArray([
            'status' => AccountStatus::VERIFIED,
            'isLocked' => false,
            'setupRequired' => false,
            'companyId' => 'company-1',
            'mentorshipCount' => 4,
        ]);

        expect($plain->toArray())->toBe([
            'status' => AccountStatus::VERIFIED,
            'isLocked' => false,
            'setupRequired' => false,
            'companyId' => null,
            'mentorshipCount' => 0,
        ]);
        expect($linked->toArray()['companyId'])->toBe('company-1');
        expect($linked->toArray()['mentorshipCount'])->toBe(4);
    });

    test('95EVB-FR-USER-019: canTransitionTo delegates to the status state machine', function (): void {
        $suspended = SupervisorEntity::fromArray(['status' => AccountStatus::SUSPENDED, 'isLocked' => true, 'setupRequired' => false]);

        expect($suspended->isLocked())->toBeTrue();
        expect($suspended->canTransitionTo(AccountStatus::VERIFIED))->toBeTrue();
        expect($suspended->canTransitionTo(AccountStatus::PROTECTED))->toBeFalse();
    });

    test('95EVB-FR-USER-019: fromArray rejects a missing status', function (): void {
        expect(fn (): SupervisorEntity => SupervisorEntity::fromArray(['isLocked' => false, 'setupRequired' => false]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('95EVB-FR-USER-019: equals and with round-trip by value', function (): void {
        $entity = SupervisorEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);

        expect($entity->equals(SupervisorEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false])))->toBeTrue();

        $locked = $entity->with('isLocked', true);

        expect($locked->isLocked())->toBeTrue();
        expect($entity->isLocked())->toBeFalse();
    });
});
