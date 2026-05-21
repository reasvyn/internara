<?php

declare(strict_types=1);

use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assignment\Models\Submission;
use App\Domain\Attendance\Models\Attendance;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Document\Models\Document;
use App\Domain\Internship\Actions\AddSupervisorReportNotesAction;
use App\Domain\Internship\Actions\ApproveReportAction;
use App\Domain\Internship\Actions\BatchUpdateInternshipStatusAction;
use App\Domain\Internship\Actions\CheckCloseReadinessAction;
use App\Domain\Internship\Actions\CreateBriefingAction;
use App\Domain\Internship\Actions\CreateInternshipAction;
use App\Domain\Internship\Actions\CreateReportAction;
use App\Domain\Internship\Actions\CreateRequirementAction;
use App\Domain\Internship\Actions\DeleteInternshipAction;
use App\Domain\Internship\Actions\DeleteRequirementAction;
use App\Domain\Internship\Actions\OverrideBriefingAttendanceAction;
use App\Domain\Internship\Actions\RecordBriefingAttendanceAction;
use App\Domain\Internship\Actions\RequestReportRevisionAction;
use App\Domain\Internship\Actions\SubmitReportAction;
use App\Domain\Internship\Actions\UpdateInternshipAction;
use App\Domain\Internship\Actions\UpdateRequirementAction;
use App\Domain\Internship\Enums\InternshipStatus;
use App\Domain\Internship\Models\Briefing;
use App\Domain\Internship\Models\BriefingAttendance;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\InternshipDocumentRequirement;
use App\Domain\Internship\Models\Report;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\Registration\Models\Registration;
use App\Domain\School\Models\AcademicYear;
use App\Domain\User\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('InternshipDomainActions', function () {
    describe('CreateInternshipAction', function () {
        it('creates an internship with active academic year', function () {
            $year = AcademicYear::factory()->create(['is_active' => true]);
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $this->actingAs($admin);

            $internship = app(CreateInternshipAction::class)->execute([
                'name' => 'Summer Internship 2026',
                'description' => 'A test internship',
                'start_date' => now()->addMonth()->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
                'status' => InternshipStatus::DRAFT->value,
            ]);

            expect($internship)->toBeInstanceOf(Internship::class)
                ->and($internship->name)->toBe('Summer Internship 2026')
                ->and($internship->academic_year_id)->toBe($year->id);
        });

        it('creates an internship without academic year', function () {
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $this->actingAs($admin);

            $internship = app(CreateInternshipAction::class)->execute([
                'name' => 'Fall Internship',
                'start_date' => now()->addMonth()->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
                'status' => InternshipStatus::DRAFT->value,
            ]);

            expect($internship)->toBeInstanceOf(Internship::class)
                ->and($internship->name)->toBe('Fall Internship');
        });
    });

    describe('UpdateInternshipAction', function () {
        it('updates internship details', function () {
            $internship = Internship::factory()->create(['name' => 'Original Name']);

            $result = app(UpdateInternshipAction::class)->execute($internship, [
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ]);

            expect($result->name)->toBe('Updated Name')
                ->and($result->description)->toBe('Updated description');
        });
    });

    describe('DeleteInternshipAction', function () {
        it('deletes an internship without registrations', function () {
            $internship = Internship::factory()->create();
            $internship->loadCount('registrations', 'placements');

            app(DeleteInternshipAction::class)->execute($internship);

            expect(Internship::find($internship->id))->toBeNull();
        });

        it('throws when internship has registrations', function () {
            $internship = Internship::factory()->create();
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            Registration::factory()->create([
                'internship_id' => $internship->id,
                'mentee_id' => $mentee->id,
            ]);
            $internship->loadCount('registrations', 'placements');

            app(DeleteInternshipAction::class)->execute($internship);
        })->throws(RejectedException::class);
    });

    describe('CreateBriefingAction', function () {
        it('creates a briefing', function () {
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $internship = Internship::factory()->create();

            $briefing = app(CreateBriefingAction::class)->execute([
                'title' => 'Welcome Briefing',
                'date' => now()->addDays(7)->toDateString(),
                'internship_id' => $internship->id,
                'created_by' => $admin->id,
            ]);

            expect($briefing)->toBeInstanceOf(Briefing::class)
                ->and($briefing->title)->toBe('Welcome Briefing')
                ->and($briefing->internship_id)->toBe($internship->id);
        });

        it('validates required fields', function () {
            app(CreateBriefingAction::class)->execute([]);
        })->throws(ValidationException::class);
    });

    describe('RecordBriefingAttendanceAction', function () {
        it('records attendance for briefing', function () {
            $briefing = Briefing::factory()->create();
            $user = User::factory()->create();

            app(RecordBriefingAttendanceAction::class)->execute($briefing, [
                ['user_id' => $user->id, 'attended' => true],
            ]);

            $attendance = BriefingAttendance::where('briefing_id', $briefing->id)
                ->where('user_id', $user->id)
                ->first();

            expect($attendance)->not->toBeNull()
                ->and($attendance->attended)->toBeTrue();
        });
    });

    describe('OverrideBriefingAttendanceAction', function () {
        it('overrides attendance status', function () {
            $attendance = BriefingAttendance::factory()->create(['attended' => false]);

            $result = app(OverrideBriefingAttendanceAction::class)->execute($attendance, true);

            expect($result->attended)->toBeTrue();
        });
    });

    describe('CreateReportAction', function () {
        it('creates a report', function () {
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);

            $report = app(CreateReportAction::class)->execute([
                'registration_id' => $registration->id,
                'title' => 'Monthly Report',
            ]);

            expect($report)->toBeInstanceOf(Report::class)
                ->and($report->title)->toBe('Monthly Report')
                ->and($report->registration_id)->toBe($registration->id);
        });
    });

    describe('SubmitReportAction', function () {
        it('submits a report', function () {
            $report = Report::factory()->create(['status' => 'draft']);

            $result = app(SubmitReportAction::class)->execute($report, [
                'chapter1' => 'Introduction content',
                'chapter2' => 'Methodology',
            ]);

            expect($result->status->value)->toBe('submitted')
                ->and($result->submitted_at)->not->toBeNull()
                ->and($result->content)->toHaveKeys(['chapter1', 'chapter2']);
        });

        it('throws on already approved report', function () {
            $report = Report::factory()->create(['status' => 'approved']);

            app(SubmitReportAction::class)->execute($report, ['content' => 'test']);
        })->throws(RejectedException::class);
    });

    describe('ApproveReportAction', function () {
        it('approves a report with score and feedback', function () {
            $user = User::factory()->create();
            $user->assignRole(Role::TEACHER->value);
            $this->actingAs($user);
            $report = Report::factory()->create(['status' => 'submitted']);

            $result = app(ApproveReportAction::class)->execute($report, [
                'score' => 85.5,
                'feedback' => 'Great work!',
            ]);

            expect($result->status->value)->toBe('approved')
                ->and($result->score)->toBe(85.5)
                ->and($result->feedback)->toBe('Great work!')
                ->and($result->graded_by)->toBe($user->id);
        });

        it('throws on already approved report', function () {
            $report = Report::factory()->create(['status' => 'approved']);

            app(ApproveReportAction::class)->execute($report, []);
        })->throws(RejectedException::class);
    });

    describe('RequestReportRevisionAction', function () {
        it('requests a revision on a submitted report', function () {
            $user = User::factory()->create();
            $user->assignRole(Role::TEACHER->value);
            $this->actingAs($user);
            $report = Report::factory()->create(['status' => 'submitted']);

            $result = app(RequestReportRevisionAction::class)->execute($report, 'Please revise chapter 2.');

            expect($result->status->value)->toBe('revision_required');
        });

        it('throws on already approved report', function () {
            $report = Report::factory()->create(['status' => 'approved']);

            app(RequestReportRevisionAction::class)->execute($report, 'feedback');
        })->throws(RejectedException::class);
    });

    describe('AddSupervisorReportNotesAction', function () {
        it('adds supervisor notes to a report', function () {
            $report = Report::factory()->create(['supervisor_notes' => null]);

            $result = app(AddSupervisorReportNotesAction::class)->execute($report, 'Good progress overall.');

            expect($result->supervisor_notes)->toBe('Good progress overall.');
        });
    });

    describe('CreateRequirementAction', function () {
        it('creates a document requirement', function () {
            $internship = Internship::factory()->create();
            $document = Document::factory()->create();

            $requirement = app(CreateRequirementAction::class)->execute(
                $internship->id,
                $document->id,
                true,
            );

            expect($requirement)->toBeInstanceOf(InternshipDocumentRequirement::class)
                ->and($requirement->internship_id)->toBe($internship->id)
                ->and($requirement->document_id)->toBe($document->id)
                ->and($requirement->is_mandatory)->toBeTrue();
        });

        it('throws on duplicate requirement', function () {
            $internship = Internship::factory()->create();
            $document = Document::factory()->create();
            app(CreateRequirementAction::class)->execute($internship->id, $document->id);

            app(CreateRequirementAction::class)->execute($internship->id, $document->id);
        })->throws(RejectedException::class);
    });

    describe('UpdateRequirementAction', function () {
        it('updates a document requirement', function () {
            $internship = Internship::factory()->create();
            $doc1 = Document::factory()->create();
            $doc2 = Document::factory()->create();
            $requirement = InternshipDocumentRequirement::factory()->create([
                'internship_id' => $internship->id,
                'document_id' => $doc1->id,
                'is_mandatory' => true,
            ]);

            $result = app(UpdateRequirementAction::class)->execute($requirement, $doc2->id, false);

            expect($result->document_id)->toBe($doc2->id)
                ->and($result->is_mandatory)->toBeFalse();
        });
    });

    describe('DeleteRequirementAction', function () {
        it('deletes a requirement', function () {
            $requirement = InternshipDocumentRequirement::factory()->create();

            app(DeleteRequirementAction::class)->execute($requirement);

            expect(InternshipDocumentRequirement::find($requirement->id))->toBeNull();
        });
    });

    describe('BatchUpdateInternshipStatusAction', function () {
        it('updates status for all internships in query', function () {
            Internship::factory()->count(3)->create(['status' => InternshipStatus::DRAFT]);

            $count = app(BatchUpdateInternshipStatusAction::class)->execute(
                Internship::where('status', InternshipStatus::DRAFT->value),
                InternshipStatus::PUBLISHED,
            );

            expect($count)->toBe(3)
                ->and(Internship::where('status', InternshipStatus::PUBLISHED->value)->count())->toBe(3);
        });
    });

    describe('CheckCloseReadinessAction', function () {
        it('returns readiness structure for an internship', function () {
            $internship = Internship::factory()->create();

            $result = app(CheckCloseReadinessAction::class)->execute($internship);

            expect($result)->toHaveKeys(['assessments', 'submissions', 'supervision_logs', 'attendance']);
        });

        it('reports pending items as not ready', function () {
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
            $registration->setStatus('active');
            $internship = $registration->internship;
            Assessment::factory()->create(['registration_id' => $registration->id, 'finalized_at' => null]);
            Attendance::factory()->create(['registration_id' => $registration->id, 'is_verified' => false]);
            SupervisionLog::factory()->create(['registration_id' => $registration->id, 'is_verified' => false]);
            Submission::factory()->create(['registration_id' => $registration->id, 'status' => 'submitted']);

            $result = app(CheckCloseReadinessAction::class)->execute($internship);

            expect($result['assessments']['passed'])->toBeFalse()
                ->and($result['submissions']['passed'])->toBeFalse()
                ->and($result['supervision_logs']['passed'])->toBeFalse()
                ->and($result['attendance']['passed'])->toBeFalse();
        });
    });
});
