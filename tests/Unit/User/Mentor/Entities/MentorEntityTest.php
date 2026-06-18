<?php

declare(strict_types=1);

use App\User\Mentor\Entities\MentorEntity;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;

function createTestUser(string $id, bool $admin = false, bool $teacher = false): User
{
    $user = new class extends User
    {
        private bool $testAdmin = false;

        private bool $testTeacher = false;

        public function setTestRoles(bool $admin, bool $teacher): void
        {
            $this->testAdmin = $admin;
            $this->testTeacher = $teacher;
        }

        public function hasRole($roles, ?string $guard = null): bool
        {
            $roles = is_array($roles) ? $roles : [$roles];

            foreach ($roles as $role) {
                $role = $role === 'super_admin' ? 'superadmin' : $role;
                if (($role === 'superadmin' || $role === 'admin') && $this->testAdmin) {
                    return true;
                }
                if ($role === 'teacher' && $this->testTeacher) {
                    return true;
                }
            }

            return false;
        }
    };

    $user->forceFill(['id' => $id]);
    $user->setTestRoles($admin, $teacher);

    return $user;
}

function createTestMentor(string $id, string $role): User
{
    $user = tap(new User)->forceFill(['id' => $id]);
    $pivot = tap(new Pivot)->forceFill(['role' => $role]);
    $user->setRelation('pivot', $pivot);

    return $user;
}

beforeEach(function () {
    $this->teacher = createTestMentor('teacher-1', 'teacher');
    $this->supervisor = createTestMentor('supervisor-1', 'supervisor');
    $this->plainMentor = tap(new User)->forceFill(['id' => 'mentor-1']);

    $this->entity = new MentorEntity(
        registrationId: 'registration-1',
        mentors: new Collection([$this->teacher, $this->supervisor, $this->plainMentor]),
    );
});

test('mentor entity returns registration id', function () {
    expect($this->entity->registrationId())->toBe('registration-1');
});

test('mentor entity returns mentors collection', function () {
    expect($this->entity->mentors())->toHaveCount(3);
});

test('mentor entity detects teacher role', function () {
    expect($this->entity->isTeacher($this->teacher))->toBeTrue();
    expect($this->entity->isTeacher($this->supervisor))->toBeFalse();
});

test('mentor entity detects supervisor role', function () {
    expect($this->entity->isSupervisor($this->supervisor))->toBeTrue();
    expect($this->entity->isSupervisor($this->teacher))->toBeFalse();
});

test('mentor entity detects mentor by primary key match', function () {
    $unknownUser = tap(new User)->forceFill(['id' => 'unknown-1']);
    expect($this->entity->isMentor($this->teacher))->toBeTrue();
    expect($this->entity->isMentor($this->supervisor))->toBeTrue();
    expect($this->entity->isMentor($this->plainMentor))->toBeTrue();
    expect($this->entity->isMentor($unknownUser))->toBeFalse();
});

test('super admin can proxy as supervisor', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canProxyAsSupervisor($user))->toBeTrue();
});

test('admin can proxy as supervisor', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canProxyAsSupervisor($user))->toBeTrue();
});

test('teacher mentor can proxy as supervisor', function () {
    $user = createTestUser('teacher-1', admin: false, teacher: true);
    expect($this->entity->canProxyAsSupervisor($user))->toBeTrue();
});

test('plain user cannot proxy as supervisor', function () {
    $user = createTestUser('some-unknown-id');
    expect($this->entity->canProxyAsSupervisor($user))->toBeFalse();
});

test('super admin can proxy as teacher', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canProxyAsTeacher($user))->toBeTrue();
});

test('admin can proxy as teacher', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canProxyAsTeacher($user))->toBeTrue();
});

test('teacher cannot proxy as teacher', function () {
    $user = createTestUser('teacher-1', admin: false, teacher: true);
    expect($this->entity->canProxyAsTeacher($user))->toBeFalse();
});

test('supervisor can verify logbook', function () {
    $user = createTestUser('supervisor-1');
    expect($this->entity->canVerifyLogbook($user))->toBeTrue();
});

test('teacher cannot verify logbook without proxy', function () {
    $user = createTestUser('teacher-1');
    expect($this->entity->canVerifyLogbook($user))->toBeFalse();
});

test('super admin can verify logbook via proxy', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canVerifyLogbook($user))->toBeTrue();
});

test('teacher can score competency as teacher evaluator', function () {
    $user = createTestUser('teacher-1');
    expect($this->entity->canScoreCompetency($user, 'teacher'))->toBeTrue();
});

test('supervisor can score competency as supervisor evaluator', function () {
    $user = createTestUser('supervisor-1');
    expect($this->entity->canScoreCompetency($user, 'supervisor'))->toBeTrue();
});

test('teacher cannot score as supervisor evaluator without proxy', function () {
    $user = createTestUser('teacher-1');
    expect($this->entity->canScoreCompetency($user, 'supervisor'))->toBeFalse();
});

test('super admin can score as supervisor evaluator via proxy', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canScoreCompetency($user, 'supervisor'))->toBeTrue();
});

test('super admin can score as teacher evaluator via proxy', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canScoreCompetency($user, 'teacher'))->toBeTrue();
});

test('plain user cannot score competency', function () {
    $user = createTestUser('some-unknown-id');
    expect($this->entity->canScoreCompetency($user, 'teacher'))->toBeFalse();
    expect($this->entity->canScoreCompetency($user, 'supervisor'))->toBeFalse();
});

test('supervisor can review supervision log', function () {
    $user = createTestUser('supervisor-1');
    expect($this->entity->canReviewSupervisionLog($user))->toBeTrue();
});

test('teacher cannot review supervision log', function () {
    $user = createTestUser('teacher-1');
    expect($this->entity->canReviewSupervisionLog($user))->toBeFalse();
});

test('teacher can grade submission', function () {
    $user = createTestUser('teacher-1');
    expect($this->entity->canGradeSubmission($user))->toBeTrue();
});

test('supervisor cannot grade submission', function () {
    $user = createTestUser('supervisor-1');
    expect($this->entity->canGradeSubmission($user))->toBeFalse();
});

test('super admin can grade submission via proxy', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canGradeSubmission($user))->toBeTrue();
});

test('teacher can verify attendance', function () {
    $user = createTestUser('teacher-1');
    expect($this->entity->canVerifyAttendance($user))->toBeTrue();
});

test('supervisor cannot verify attendance', function () {
    $user = createTestUser('supervisor-1');
    expect($this->entity->canVerifyAttendance($user))->toBeFalse();
});

test('super admin can verify attendance via proxy', function () {
    $user = createTestUser('admin-1', admin: true);
    expect($this->entity->canVerifyAttendance($user))->toBeTrue();
});
