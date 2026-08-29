<?php

declare(strict_types=1);

use App\Modules\Assessment\Policies\AssessmentPolicy;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Attendance\Policies\AttendancePolicy;
use App\Modules\Journals\Domain\Logbook\Policies\LogbookPolicy;
use App\Modules\Journals\Domain\SupervisionLog\Policies\SupervisionLogPolicy;
use App\Modules\User\Models\User;
use App\Modules\User\Policies\Concerns\HasMentorProxy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('T4B26-FR-CRP1: proxy-capable policies use HasMentorProxy', function () {
    $policies = [
        AttendancePolicy::class,
        LogbookPolicy::class,
        SupervisionLogPolicy::class,
        AssessmentPolicy::class,
    ];

    foreach ($policies as $policy) {
        expect(in_array(HasMentorProxy::class, class_uses_recursive($policy), true))
            ->toBeTrue(sprintf('%s must use HasMentorProxy', $policy));
    }
});

test('T4B26-FR-CRP2: teacher can proxy as supervisor for a student they mentor', function () {
    $teacher = User::factory()->create()->assignRole('teacher');
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $registration = Registration::factory()->create()->setRelation('mentors', new Collection([$teacher, $supervisor]));

    $mentor = $registration->asMentorEntity();

    expect($mentor->isTeacher($teacher))->toBeTrue()
        ->and($mentor->canProxyAsSupervisor($teacher))->toBeTrue();
});

test('T4B26-FR-CRP2: teacher cannot proxy as supervisor without a mentor relationship', function () {
    $teacher = User::factory()->create()->assignRole('teacher');
    $registration = Registration::factory()->create();

    $mentor = $registration->asMentorEntity();

    expect($mentor->isMentor($teacher))->toBeFalse()
        ->and($mentor->canProxyAsSupervisor($teacher))->toBeFalse();
});

test('T4B26-FR-CRP3: admin and super admin proxy for teacher or supervisor on any record', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $super = User::factory()->create()->assignRole('super_admin');
    $registration = Registration::factory()->create();

    $mentor = $registration->asMentorEntity();

    expect($mentor->canProxyAsTeacher($admin))->toBeTrue()
        ->and($mentor->canProxyAsTeacher($super))->toBeTrue()
        ->and($mentor->canProxyAsSupervisor($admin))->toBeTrue()
        ->and($mentor->canProxyAsSupervisor($super))->toBeTrue();
});

test('T4B26-FR-CRP5: proxy verification applies to logbook and supervision log', function () {
    $teacher = User::factory()->create()->assignRole('teacher');
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $registration = Registration::factory()->create()->setRelation('mentors', new Collection([$teacher, $supervisor]));

    $mentor = $registration->asMentorEntity();

    expect($mentor->canVerifyLogbook($teacher))->toBeTrue()
        ->and($mentor->canReviewSupervisionLog($teacher))->toBeTrue();
});

test('T4B26-FR-CRP5: supervisor performs proxy verification directly', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $registration = Registration::factory()->create()->setRelation('mentors', new Collection([$supervisor]));

    $mentor = $registration->asMentorEntity();

    expect($mentor->isSupervisor($supervisor))->toBeTrue()
        ->and($mentor->canVerifyLogbook($supervisor))->toBeTrue()
        ->and($mentor->canReviewSupervisionLog($supervisor))->toBeTrue();
});

test('T4B26-FR-CRP5: assessment grading honors evaluator role and mentor match', function () {
    $teacher = User::factory()->create()->assignRole('teacher');
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $registration = Registration::factory()->create()->setRelation('mentors', new Collection([$teacher, $supervisor]));

    $mentor = $registration->asMentorEntity();

    expect($mentor->canScoreCompetency($teacher, 'teacher'))->toBeTrue()
        ->and($mentor->canScoreCompetency($supervisor, 'supervisor'))->toBeTrue()
        ->and($mentor->canScoreCompetency($teacher, 'supervisor'))->toBeTrue()
        ->and($mentor->canScoreCompetency($supervisor, 'teacher'))->toBeFalse();
});
