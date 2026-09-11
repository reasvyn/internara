<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Entities\StudentEntity;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

describe('95EVB: student entity', function (): void {
    $registration = function (string $status): Registration {
        $internship = new Internship(['phases' => [['name' => 'Orientation', 'order' => 1, 'weight' => 100]]]);
        $model = new Registration([
            'status' => $status,
            'start_date' => Carbon::parse('2026-01-01 00:00:00'),
            'end_date' => Carbon::parse('2026-06-30 00:00:00'),
        ]);
        $model->setRelation('internship', $internship);

        return $model;
    };

    $studentUser = function (array $registrations): User {
        $user = new User([
            'name' => 'Student',
            'email' => 'student@test.local',
            'username' => 'student',
            'status' => AccountStatus::VERIFIED,
        ]);
        $user->setRelation('registrations', new EloquentCollection($registrations));

        return $user;
    };

    test('95EVB-FR-USER-019: fromModel bridges the account snapshot without persisting', function () use ($studentUser): void {
        $user = $studentUser([]);
        $user->setAttribute('locked_at', null);
        $user->setAttribute('setup_required', false);

        $entity = StudentEntity::fromModel($user);

        expect($entity->status())->toBe(AccountStatus::VERIFIED);
        expect($entity->isLocked())->toBeFalse();
        expect($entity->requiresSetup())->toBeFalse();
        expect($entity->isRegistered())->toBeFalse();
        expect($user->exists)->toBeFalse();
    });

    test('detects an active registration from the loaded relation', function () use ($studentUser, $registration): void {
        $active = $studentUser([$registration('active')]);
        $pending = $studentUser([$registration('pending')]);

        expect(StudentEntity::fromModel($active)->isRegistered())->toBeTrue();
        expect(StudentEntity::fromModel($pending)->isRegistered())->toBeFalse();
        expect($active->exists)->toBeFalse();
    });

    test('95EVB-FR-USER-015: suspension, archive, and inactivity answer the stored status', function (): void {
        $base = ['isLocked' => false, 'setupRequired' => false];

        expect(StudentEntity::fromArray([...$base, 'status' => AccountStatus::SUSPENDED])->isSuspended())->toBeTrue();
        expect(StudentEntity::fromArray([...$base, 'status' => AccountStatus::ARCHIVED])->isArchived())->toBeTrue();
        expect(StudentEntity::fromArray([...$base, 'status' => AccountStatus::INACTIVE])->isInactive())->toBeTrue();
        expect(StudentEntity::fromArray([...$base, 'status' => AccountStatus::VERIFIED])->isSuspended())->toBeFalse();
    });

    test('exposes registration and placement flags with false defaults', function (): void {
        $plain = StudentEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);
        $placed = StudentEntity::fromArray([
            'status' => AccountStatus::VERIFIED,
            'isLocked' => false,
            'setupRequired' => false,
            'hasActiveRegistration' => true,
            'hasPlacement' => true,
        ]);

        expect($plain->isRegistered())->toBeFalse();
        expect($plain->isPlaced())->toBeFalse();
        expect($placed->isRegistered())->toBeTrue();
        expect($placed->isPlaced())->toBeTrue();
    });

    test('95EVB-FR-USER-019: canTransitionTo delegates to the status state machine', function (): void {
        $verified = StudentEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);

        expect($verified->canTransitionTo(AccountStatus::INACTIVE))->toBeTrue();
        expect($verified->canTransitionTo(AccountStatus::PROTECTED))->toBeFalse();
    });

    test('95EVB-FR-USER-019: fromArray rejects a missing status', function (): void {
        expect(fn (): StudentEntity => StudentEntity::fromArray(['isLocked' => false, 'setupRequired' => false]))
            ->toThrow(InvalidArgumentException::class, 'status');
    });

    test('95EVB-FR-USER-019: toArray, equals, and with round-trip by value', function (): void {
        $entity = StudentEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false]);

        expect($entity->toArray())->toBe([
            'status' => AccountStatus::VERIFIED,
            'isLocked' => false,
            'setupRequired' => false,
            'hasActiveRegistration' => false,
            'hasPlacement' => false,
        ]);
        expect($entity->equals(StudentEntity::fromArray(['status' => AccountStatus::VERIFIED, 'isLocked' => false, 'setupRequired' => false])))->toBeTrue();

        $placed = $entity->with('hasPlacement', true);

        expect($placed->isPlaced())->toBeTrue();
        expect($entity->isPlaced())->toBeFalse();
    });
});
