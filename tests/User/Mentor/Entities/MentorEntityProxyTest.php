<?php

declare(strict_types=1);

use App\User\Mentor\Entities\MentorEntity;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

function proxyUser(string $id, string $role): User
{
    $user = new class extends User
    {
        private string $testRole;

        public function setTestRole(string $role): void
        {
            $this->testRole = $role;
        }

        public function hasRole($roles, ?string $guard = null): bool
        {
            $targets = is_array($roles) ? $roles : [$roles];

            return in_array($this->testRole, $targets, true);
        }

        public function hasAnyRole(...$roles): bool
        {
            return in_array($this->testRole, $roles, true);
        }
    };

    $user->forceFill(['id' => $id]);
    $user->setTestRole($role);

    return $user;
}

function proxyEntity(string $registrationId, array $mentorData): MentorEntity
{
    $mentors = new Collection;

    foreach ($mentorData as $data) {
        $mentor = proxyUser($data['id'], $data['role']);
        $mentor->setRelation('pivot', (object) ['role' => $data['role']]);
        $mentors->push($mentor);
    }

    return new MentorEntity(registrationId: $registrationId, mentors: $mentors);
}

test('teacher can proxy as supervisor for assigned student', function () {
    $teacher = proxyUser('t-1', 'teacher');
    $entity = proxyEntity('reg-1', [
        ['id' => 't-1', 'role' => 'teacher'],
    ]);

    expect($entity->canProxyAsSupervisor($teacher))->toBeTrue();
    expect($entity->canVerifyLogbook($teacher))->toBeTrue();
    expect($entity->canReviewSupervisionLog($teacher))->toBeTrue();
});

test('unrelated teacher cannot proxy as supervisor', function () {
    $teacher = proxyUser('t-2', 'teacher');
    $entity = proxyEntity('reg-1', [
        ['id' => 't-1', 'role' => 'teacher'],
    ]);

    expect($entity->canProxyAsSupervisor($teacher))->toBeFalse();
    expect($entity->canVerifyLogbook($teacher))->toBeFalse();
});

test('admin can proxy as teacher', function () {
    $admin = proxyUser('a-1', 'admin');
    $entity = proxyEntity('reg-1', []);

    expect($entity->canProxyAsTeacher($admin))->toBeTrue();
    expect($entity->canGradeSubmission($admin))->toBeTrue();
    expect($entity->canVerifyAttendance($admin))->toBeTrue();
});

test('supervisor has direct access', function () {
    $supervisor = proxyUser('s-1', 'supervisor');
    $entity = proxyEntity('reg-1', [
        ['id' => 's-1', 'role' => 'supervisor'],
    ]);

    expect($entity->canVerifyLogbook($supervisor))->toBeTrue();
    expect($entity->canReviewSupervisionLog($supervisor))->toBeTrue();
});

test('teacher has direct access for grading and attendance', function () {
    $teacher = proxyUser('t-1', 'teacher');
    $entity = proxyEntity('reg-1', [
        ['id' => 't-1', 'role' => 'teacher'],
    ]);

    expect($entity->canGradeSubmission($teacher))->toBeTrue();
    expect($entity->canVerifyAttendance($teacher))->toBeTrue();
});

test('super admin bypasses all proxy checks', function () {
    $superAdmin = proxyUser('sa-1', 'super_admin');
    $entity = proxyEntity('reg-1', []);

    expect($entity->canProxyAsTeacher($superAdmin))->toBeTrue();
    expect($entity->canProxyAsSupervisor($superAdmin))->toBeTrue();
});
