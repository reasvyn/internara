<?php

declare(strict_types=1);

use App\Modules\User\Entities\TeacherEntity;
use App\Modules\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;

final class TeacherEntityModelDouble extends Model
{
    protected $guarded = [];
}

describe('95EVB: teacher entity', function (): void {
    test('95EVB-FR-USER-019: fromModel bridges the account snapshot without persisting', function (): void {
        $model = new TeacherEntityModelDouble([
            'status' => 'verified',
            'locked_at' => null,
            'setup_required' => true,
        ]);

        $entity = TeacherEntity::fromModel($model);

        expect($entity->status())->toBe(AccountStatus::VERIFIED);
        expect($entity->isLocked())->toBeFalse();
        expect($entity->requiresSetup())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('95EVB-FR-USER-015: suspension, archive, and inactivity answer the stored status', function (): void {
        $base = ['isLocked' => false, 'setupRequired' => false];

        expect(TeacherEntity::fromArray([...$base, 'status' => AccountStatus::SUSPENDED])->isSuspended())->toBeTrue();
        expect(TeacherEntity::fromArray([...$base, 'status' => AccountStatus::ARCHIVED])->isArchived())->toBeTrue();
        expect(TeacherEntity::fromArray([...$base, 'status' => AccountStatus::INACTIVE])->isInactive())->toBeTrue();
        expect(TeacherEntity::fromArray([...$base, 'status' => AccountStatus::VERIFIED])->isArchived())->toBeFalse();
    });

    test('exposes the mentorship count with a zero default', function (): void {
        $plain = TeacherEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);
        $mentor = TeacherEntity::fromArray([
            'status' => AccountStatus::VERIFIED,
            'isLocked' => false,
            'setupRequired' => false,
            'mentorshipCount' => 6,
        ]);

        expect($plain->toArray()['mentorshipCount'])->toBe(0);
        expect($mentor->toArray()['mentorshipCount'])->toBe(6);
    });

    test('95EVB-FR-USER-019: canTransitionTo delegates to the status state machine', function (): void {
        $inactive = TeacherEntity::fromArray(['status' => AccountStatus::INACTIVE, 'isLocked' => false, 'setupRequired' => false]);

        expect($inactive->canTransitionTo(AccountStatus::VERIFIED))->toBeTrue();
        expect($inactive->canTransitionTo(AccountStatus::PROTECTED))->toBeFalse();
    });

    test('95EVB-FR-USER-019: fromArray rejects a missing status', function (): void {
        expect(fn (): TeacherEntity => TeacherEntity::fromArray(['isLocked' => false, 'setupRequired' => false]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('95EVB-FR-USER-019: equals and with round-trip by value', function (): void {
        $entity = TeacherEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);

        expect($entity->equals(TeacherEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false])))->toBeTrue();
        expect($entity->equals(TeacherEntity::fromArray(['status' => AccountStatus::SUSPENDED, 'isLocked' => false, 'setupRequired' => false])))->toBeFalse();

        $setup = $entity->with('setupRequired', true);

        expect($setup->requiresSetup())->toBeTrue();
        expect($entity->requiresSetup())->toBeFalse();
    });
});
