<?php

declare(strict_types=1);

use App\Modules\Assessment\Actions\FinalizeAssessmentAction;
use App\Modules\Assessment\Actions\ScoreIndicatorAction;
use App\Modules\Assessment\Data\ScoreIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Attendance\Actions\VerifyAttendanceAction;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\SupervisionLog\Actions\ReviewLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Actions\VerifySupervisionLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Data\ReviewLogData;
use App\Modules\Journal\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('T4B26: Cross-role proxy activity log metadata tagging (FR-CRP6, FR-RBAC-021)', function (): void {
    test('T4B26-FR-CRP6: VerifyAttendanceAction tags proxy metadata when verified by admin and untagged for teacher', function (): void {
        $registration = Registration::factory()->create();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $teacher->id,
            'role' => 'teacher',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $attendanceTeacher = Attendance::factory()->create([
            'registration_id' => $registration->id,
            'is_verified' => false,
        ]);
        $attendanceAdmin = Attendance::factory()->create([
            'registration_id' => $registration->id,
            'is_verified' => false,
        ]);

        // 1. Teacher verifies attendance -> native role, no proxy metadata
        app(VerifyAttendanceAction::class)->execute($attendanceTeacher, $teacher);
        $logTeacher = Activity::where('event', 'attendance_verified')
            ->where('subject_id', $attendanceTeacher->id)
            ->latest('id')
            ->first();

        expect($logTeacher)->not->toBeNull();
        expect($logTeacher->properties->has('proxy_role'))->toBeFalse();

        // 2. Admin verifies attendance -> proxy for teacher
        app(VerifyAttendanceAction::class)->execute($attendanceAdmin, $admin);
        $logAdmin = Activity::where('event', 'attendance_verified')
            ->where('subject_id', $attendanceAdmin->id)
            ->latest('id')
            ->first();

        expect($logAdmin)->not->toBeNull();
        expect($logAdmin->properties->get('proxy_role'))->toBe('teacher');
        expect($logAdmin->properties->get('proxy_reason'))->toBe('admin-proxy-for-teacher');
        expect(data_get($logAdmin->properties->toArray(), 'payload.proxy_role'))->toBe('teacher');
    });

    test('T4B26-FR-CRP6: VerifySupervisionLogAction tags proxy metadata when verified by teacher and untagged for supervisor', function (): void {
        $registration = Registration::factory()->create();

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $supervisor->id,
            'role' => 'supervisor',
        ]);

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $teacher->id,
            'role' => 'teacher',
        ]);

        $logNative = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'status' => SupervisionLogStatus::SUBMITTED->value,
            'is_verified' => false,
        ]);
        $logProxied = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'status' => SupervisionLogStatus::SUBMITTED->value,
            'is_verified' => false,
        ]);

        // 1. Supervisor verifies -> native, no proxy
        app(VerifySupervisionLogAction::class)->execute($logNative, $supervisor);
        $activityNative = Activity::where('event', 'supervision_log_verified')
            ->where('subject_id', $logNative->id)
            ->latest('id')
            ->first();

        expect($activityNative)->not->toBeNull();
        expect($activityNative->properties->has('proxy_role'))->toBeFalse();

        // 2. Teacher verifies -> mentor proxy for supervisor
        app(VerifySupervisionLogAction::class)->execute($logProxied, $teacher);
        $activityProxied = Activity::where('event', 'supervision_log_verified')
            ->where('subject_id', $logProxied->id)
            ->latest('id')
            ->first();

        expect($activityProxied)->not->toBeNull();
        expect($activityProxied->properties->get('proxy_role'))->toBe('supervisor');
        expect($activityProxied->properties->get('proxy_reason'))->toBe('mentor-proxy-for-supervisor');
        expect(data_get($activityProxied->properties->toArray(), 'payload.proxy_role'))->toBe('supervisor');
    });

    test('T4B26-FR-CRP6: ReviewLogAction tags proxy metadata when reviewed by teacher and untagged for supervisor', function (): void {
        $registration = Registration::factory()->create();

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $supervisor->id,
            'role' => 'supervisor',
        ]);

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $teacher->id,
            'role' => 'teacher',
        ]);

        $logNative = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'status' => SupervisionLogStatus::SUBMITTED->value,
            'supervisor_id' => $supervisor->id,
        ]);
        $logProxied = SupervisionLog::factory()->create([
            'registration_id' => $registration->id,
            'status' => SupervisionLogStatus::SUBMITTED->value,
            'supervisor_id' => $supervisor->id,
        ]);

        // 1. Supervisor reviews -> native
        app(ReviewLogAction::class)->execute(new ReviewLogData($logNative->id, $supervisor->id, 'Good progress'));
        $activityNative = Activity::where('event', 'supervision_log_reviewed')
            ->where('subject_id', $logNative->id)
            ->latest('id')
            ->first();

        expect($activityNative)->not->toBeNull();
        expect($activityNative->properties->has('proxy_role'))->toBeFalse();

        // 2. Teacher reviews -> proxy for supervisor
        app(ReviewLogAction::class)->execute(new ReviewLogData($logProxied->id, $teacher->id, 'Teacher review note'));
        $activityProxied = Activity::where('event', 'supervision_log_reviewed')
            ->where('subject_id', $logProxied->id)
            ->latest('id')
            ->first();

        expect($activityProxied)->not->toBeNull();
        expect($activityProxied->properties->get('proxy_role'))->toBe('supervisor');
        expect($activityProxied->properties->get('proxy_reason'))->toBe('mentor-proxy-for-supervisor');
    });

    test('T4B26-FR-CRP6: ScoreIndicatorAction tags proxy metadata when teacher scores supervisor competency and untagged when supervisor scores', function (): void {
        $registration = Registration::factory()->create();

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $supervisor->id,
            'role' => 'supervisor',
        ]);

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $teacher->id,
            'role' => 'teacher',
        ]);

        $rubric = Rubric::factory()->create([
            'is_active' => true,
            'structure' => [
                'competencies' => [
                    [
                        'id' => 'comp-sup',
                        'name' => 'Industry competency',
                        'evaluator_role' => 'supervisor',
                        'indicators' => [
                            [
                                'id' => 'ind-1',
                                'name' => 'Punctuality',
                                'max_score' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $assessment = Assessment::factory()->create([
            'registration_id' => $registration->id,
            'rubric_id' => $rubric->id,
            'finalized_at' => null,
            'scores_data' => null,
        ]);

        // 1. Supervisor scores supervisor competency -> native
        app(ScoreIndicatorAction::class)->execute(
            $assessment,
            $rubric,
            new ScoreIndicatorData('comp-sup', 'ind-1', 9.0),
            $supervisor,
        );

        $activityNative = Activity::where('event', 'indicator_scored')
            ->where('subject_id', $assessment->id)
            ->latest('id')
            ->first();

        expect($activityNative)->not->toBeNull();
        expect($activityNative->properties->has('proxy_role'))->toBeFalse();

        // 2. Advance time past the supervisor inactivity window (48h) so teacher proxy is allowed
        Carbon::setTestNow(now()->addHours(50));

        // Teacher scores supervisor competency -> proxy for supervisor
        app(ScoreIndicatorAction::class)->execute(
            $assessment->fresh(),
            $rubric,
            new ScoreIndicatorData('comp-sup', 'ind-1', 8.5),
            $teacher,
        );

        $activityProxied = Activity::where('event', 'indicator_scored')
            ->where('subject_id', $assessment->id)
            ->latest('id')
            ->first();

        expect($activityProxied)->not->toBeNull();
        expect($activityProxied->properties->get('proxy_role'))->toBe('supervisor');
        expect($activityProxied->properties->get('proxy_reason'))->toBe('mentor-proxy-for-supervisor');

        Carbon::setTestNow();
    });

    test('T4B26-FR-CRP6: FinalizeAssessmentAction tags proxy metadata when finalized by admin and untagged for teacher', function (): void {
        $registration = Registration::factory()->create();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $teacher->id,
            'role' => 'teacher',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $rubric = Rubric::factory()->create([
            'is_active' => true,
            'structure' => [
                'competencies' => [
                    [
                        'id' => 'comp-1',
                        'weight' => 100,
                        'evaluator_role' => 'teacher',
                        'indicators' => [
                            [
                                'id' => 'ind-1',
                                'max_score' => 10,
                                'weight' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $assessmentTeacher = Assessment::factory()->create([
            'registration_id' => $registration->id,
            'rubric_id' => $rubric->id,
            'finalized_at' => null,
            'scores_data' => [
                'competencies' => [
                    [
                        'id' => 'comp-1',
                        'indicators' => ['ind-1' => 9.0],
                    ],
                ],
            ],
        ]);

        $assessmentAdmin = Assessment::factory()->create([
            'registration_id' => $registration->id,
            'rubric_id' => $rubric->id,
            'finalized_at' => null,
            'scores_data' => [
                'competencies' => [
                    [
                        'id' => 'comp-1',
                        'indicators' => ['ind-1' => 9.0],
                    ],
                ],
            ],
        ]);

        // 1. Teacher finalizes -> native
        app(FinalizeAssessmentAction::class)->execute($assessmentTeacher, $teacher);
        $logTeacher = Activity::where('event', 'assessment_finalized')
            ->where('subject_id', $assessmentTeacher->id)
            ->latest('id')
            ->first();

        expect($logTeacher)->not->toBeNull();
        expect($logTeacher->properties->has('proxy_role'))->toBeFalse();

        // 2. Admin finalizes -> proxy for teacher
        app(FinalizeAssessmentAction::class)->execute($assessmentAdmin, $admin);
        $logAdmin = Activity::where('event', 'assessment_finalized')
            ->where('subject_id', $assessmentAdmin->id)
            ->latest('id')
            ->first();

        expect($logAdmin)->not->toBeNull();
        expect($logAdmin->properties->get('proxy_role'))->toBe('teacher');
        expect($logAdmin->properties->get('proxy_reason'))->toBe('admin-proxy-for-teacher');
    });
});
