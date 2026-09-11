<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Domain\Mentor\Entities\MentorEntity;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\Permission\Models\Role as RoleModel;

describe('T4B26: mentor entity', function (): void {
    $makeUser = function (string $id, array $roles): User {
        $user = new User([
            'name' => 'User '.$id,
            'email' => $id.'@test.local',
            'username' => $id,
            'status' => AccountStatus::VERIFIED,
        ]);
        $user->setAttribute('id', $id);
        $user->setRelation(
            'roles',
            new EloquentCollection(array_map(
                fn (string $role): RoleModel => new RoleModel(['name' => $role, 'guard_name' => 'web']),
                $roles,
            ))
        );

        return $user;
    };

    $makeEntity = function (User ...$mentors): MentorEntity {
        $registration = new Registration([
            'status' => 'active',
            'start_date' => Carbon::parse('2026-01-01 00:00:00'),
            'end_date' => Carbon::parse('2026-06-30 00:00:00'),
        ]);
        $registration->setAttribute('id', 'registration-1');
        $registration->setRelation('mentors', new EloquentCollection(array_values($mentors)));

        return MentorEntity::fromModel($registration);
    };

    test('T4B26-FR-RBAC-006: fromModel bridges the mentors collection without persisting', function () use ($makeUser, $makeEntity): void {
        $teacher = $makeUser('teacher-1', ['teacher']);

        $entity = $makeEntity($teacher);

        expect($entity->registrationId())->toBe('registration-1');
        expect($entity->mentors())->toHaveCount(1);
        expect($entity->isMentor($teacher))->toBeTrue();
    });

    test('T4B26-FR-RBAC-006: role queries match identity plus grant together', function () use ($makeUser, $makeEntity): void {
        $teacher = $makeUser('teacher-1', ['teacher']);
        $supervisor = $makeUser('supervisor-1', ['supervisor']);
        $outsiderTeacher = $makeUser('teacher-2', ['teacher']);

        $entity = $makeEntity($teacher, $supervisor);

        expect($entity->isTeacher($teacher))->toBeTrue();
        expect($entity->isTeacher($outsiderTeacher))->toBeFalse();
        expect($entity->isTeacher($supervisor))->toBeFalse();
        expect($entity->isSupervisor($supervisor))->toBeTrue();
        expect($entity->isSupervisor($teacher))->toBeFalse();
        expect($entity->isMentor($outsiderTeacher))->toBeFalse();
    });

    test('T4B26-FR-RBAC-017: teacher proxies as supervisor only inside its own mentorship', function () use ($makeUser, $makeEntity): void {
        $teacher = $makeUser('teacher-1', ['teacher']);
        $supervisor = $makeUser('supervisor-1', ['supervisor']);
        $student = $makeUser('student-1', ['student']);

        $entity = $makeEntity($teacher, $supervisor);

        expect($entity->canProxyAsSupervisor($teacher))->toBeTrue();
        expect($entity->canProxyAsSupervisor($supervisor))->toBeFalse();
        expect($entity->canProxyAsSupervisor($student))->toBeFalse();
    });

    test('T4B26-FR-RBAC-017: admin proxies as supervisor and teacher on any record', function () use ($makeUser, $makeEntity): void {
        $admin = $makeUser('admin-1', ['admin']);
        $teacher = $makeUser('teacher-1', ['teacher']);

        $entity = $makeEntity();

        expect($entity->canProxyAsSupervisor($admin))->toBeTrue();
        expect($entity->canProxyAsTeacher($admin))->toBeTrue();
        expect($entity->canProxyAsTeacher($teacher))->toBeFalse();
        expect($entity->canVerifyAttendance($teacher))->toBeFalse();
    });

    test('T4B26-FR-RBAC-018: logbook verification honours supervisors and their proxies', function () use ($makeUser, $makeEntity): void {
        $teacher = $makeUser('teacher-1', ['teacher']);
        $supervisor = $makeUser('supervisor-1', ['supervisor']);
        $admin = $makeUser('admin-1', ['admin']);
        $student = $makeUser('student-1', ['student']);

        $entity = $makeEntity($teacher, $supervisor);

        expect($entity->canVerifyLogbook($supervisor))->toBeTrue();
        expect($entity->canVerifyLogbook($teacher))->toBeTrue();
        expect($entity->canVerifyLogbook($admin))->toBeTrue();
        expect($entity->canVerifyLogbook($student))->toBeFalse();
    });

    test('T4B26-FR-RBAC-018: competency scoring matches evaluator role against mentorship', function () use ($makeUser, $makeEntity): void {
        $teacher = $makeUser('teacher-1', ['teacher']);
        $supervisor = $makeUser('supervisor-1', ['supervisor']);
        $admin = $makeUser('admin-1', ['admin']);

        $entity = $makeEntity($teacher, $supervisor);

        expect($entity->canScoreCompetency($teacher, 'teacher'))->toBeTrue();
        expect($entity->canScoreCompetency($supervisor, 'teacher'))->toBeFalse();
        expect($entity->canScoreCompetency($supervisor, 'supervisor'))->toBeTrue();
        expect($entity->canScoreCompetency($teacher, 'supervisor'))->toBeTrue();
        expect($entity->canScoreCompetency($admin, 'teacher'))->toBeTrue();
    });

    test('T4B26-FR-RBAC-018: supervision review, grading, and attendance follow the proxy matrix', function () use ($makeUser, $makeEntity): void {
        $teacher = $makeUser('teacher-1', ['teacher']);
        $supervisor = $makeUser('supervisor-1', ['supervisor']);
        $admin = $makeUser('admin-1', ['admin']);

        $entity = $makeEntity($teacher, $supervisor);

        expect($entity->canReviewSupervisionLog($supervisor))->toBeTrue();
        expect($entity->canReviewSupervisionLog($teacher))->toBeTrue();
        expect($entity->canGradeSubmission($teacher))->toBeTrue();
        expect($entity->canGradeSubmission($supervisor))->toBeFalse();
        expect($entity->canGradeSubmission($admin))->toBeTrue();
        expect($entity->canVerifyAttendance($teacher))->toBeTrue();
        expect($entity->canVerifyAttendance($supervisor))->toBeFalse();
        expect($entity->canVerifyAttendance($admin))->toBeTrue();
    });
});
