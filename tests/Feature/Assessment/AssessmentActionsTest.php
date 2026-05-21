<?php

declare(strict_types=1);

use App\Domain\Assessment\Actions\AutoCalculateAssessmentAction;
use App\Domain\Assessment\Actions\CompletePresentationAction;
use App\Domain\Assessment\Actions\CreateCompetencyAction;
use App\Domain\Assessment\Actions\CreateIndicatorAction;
use App\Domain\Assessment\Actions\CreateRubricAction;
use App\Domain\Assessment\Actions\DeleteCompetencyAction;
use App\Domain\Assessment\Actions\DeleteIndicatorAction;
use App\Domain\Assessment\Actions\DeleteRubricAction;
use App\Domain\Assessment\Actions\FinalizeAssessmentAction;
use App\Domain\Assessment\Actions\InitializeAssessmentAction;
use App\Domain\Assessment\Actions\SchedulePresentationAction;
use App\Domain\Assessment\Actions\ScoreIndicatorAction;
use App\Domain\Assessment\Actions\ScorePresentationAction;
use App\Domain\Assessment\Actions\UpdateAssessmentScoresAction;
use App\Domain\Assessment\Actions\UpdateCompetencyAction;
use App\Domain\Assessment\Actions\UpdateIndicatorAction;
use App\Domain\Assessment\Actions\UpdateRubricAction;
use App\Domain\Assessment\Enums\EvaluatorRole;
use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assessment\Models\Competency;
use App\Domain\Assessment\Models\Indicator;
use App\Domain\Assessment\Models\Presentation;
use App\Domain\Assessment\Models\PresentationExaminer;
use App\Domain\Assessment\Models\Rubric;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\Report;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('AssessmentDomainActions', function () {
    describe('CreateRubricAction', function () {
        it('creates a rubric', function () {
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $this->actingAs($admin);

            $rubric = app(CreateRubricAction::class)->execute('Final Assessment Rubric', 'Description');

            expect($rubric)->toBeInstanceOf(Rubric::class)
                ->and($rubric->name)->toBe('Final Assessment Rubric')
                ->and($rubric->description)->toBe('Description')
                ->and($rubric->is_active)->toBeTrue()
                ->and($rubric->created_by)->toBe($admin->id);
        });
    });

    describe('UpdateRubricAction', function () {
        it('updates a rubric', function () {
            $rubric = Rubric::factory()->create(['name' => 'Old Name']);

            $result = app(UpdateRubricAction::class)->execute($rubric, 'New Name', 'New Desc', false);

            expect($result->name)->toBe('New Name')
                ->and($result->description)->toBe('New Desc')
                ->and($result->is_active)->toBeFalse();
        });
    });

    describe('DeleteRubricAction', function () {
        it('deletes a rubric', function () {
            $rubric = Rubric::factory()->create();

            app(DeleteRubricAction::class)->execute($rubric);

            expect(Rubric::find($rubric->id))->toBeNull();
        });
    });

    describe('CreateCompetencyAction', function () {
        it('creates a competency', function () {
            $rubric = Rubric::factory()->create();

            $competency = app(CreateCompetencyAction::class)->execute(
                $rubric->id,
                'Communication Skills',
                'Ability to communicate effectively',
                40,
                EvaluatorRole::TEACHER,
                1,
            );

            expect($competency)->toBeInstanceOf(Competency::class)
                ->and($competency->rubric_id)->toBe($rubric->id)
                ->and($competency->name)->toBe('Communication Skills')
                ->and($competency->weight)->toBe(40)
                ->and($competency->evaluator_role->value)->toBe(EvaluatorRole::TEACHER->value);
        });
    });

    describe('UpdateCompetencyAction', function () {
        it('updates a competency', function () {
            $competency = Competency::factory()->create(['name' => 'Old', 'weight' => 20]);

            $result = app(UpdateCompetencyAction::class)->execute(
                $competency,
                'Updated Competency',
                'Updated description',
                50,
                EvaluatorRole::SUPERVISOR,
                2,
            );

            expect($result->name)->toBe('Updated Competency')
                ->and($result->weight)->toBe(50)
                ->and($result->evaluator_role->value)->toBe(EvaluatorRole::SUPERVISOR->value);
        });
    });

    describe('DeleteCompetencyAction', function () {
        it('deletes a competency', function () {
            $competency = Competency::factory()->create();

            app(DeleteCompetencyAction::class)->execute($competency);

            expect(Competency::find($competency->id))->toBeNull();
        });
    });

    describe('CreateIndicatorAction', function () {
        it('creates an indicator', function () {
            $competency = Competency::factory()->create();

            $indicator = app(CreateIndicatorAction::class)->execute(
                $competency->id,
                'Clarity of Expression',
                'How clearly ideas are expressed',
                100,
                50,
                1,
            );

            expect($indicator)->toBeInstanceOf(Indicator::class)
                ->and($indicator->competency_id)->toBe($competency->id)
                ->and($indicator->name)->toBe('Clarity of Expression')
                ->and($indicator->max_score)->toBe(100.0)
                ->and($indicator->weight)->toBe(50);
        });
    });

    describe('UpdateIndicatorAction', function () {
        it('updates an indicator', function () {
            $indicator = Indicator::factory()->create(['name' => 'Old', 'max_score' => 50]);

            $result = app(UpdateIndicatorAction::class)->execute(
                $indicator,
                'Updated Indicator',
                'Updated',
                80,
                60,
                2,
            );

            expect($result->name)->toBe('Updated Indicator')
                ->and($result->max_score)->toBe(80.0)
                ->and($result->weight)->toBe(60);
        });
    });

    describe('DeleteIndicatorAction', function () {
        it('deletes an indicator', function () {
            $indicator = Indicator::factory()->create();

            app(DeleteIndicatorAction::class)->execute($indicator);

            expect(Indicator::find($indicator->id))->toBeNull();
        });
    });

    describe('InitializeAssessmentAction', function () {
        it('creates a new assessment with rubric', function () {
            $internship = Internship::factory()->create();
            $rubric = Rubric::factory()->create(['internship_id' => $internship->id, 'is_active' => true]);
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create([
                'internship_id' => $internship->id,
                'mentee_id' => $mentee->id,
            ]);

            $result = app(InitializeAssessmentAction::class)->execute($registration->id);

            expect($result)->toHaveKeys(['assessment', 'rubric'])
                ->and($result['assessment'])->not->toBeNull()
                ->and($result['assessment']->registration_id)->toBe($registration->id)
                ->and($result['rubric']->id)->toBe($rubric->id);
        });

        it('returns null when no active rubric exists', function () {
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);

            $result = app(InitializeAssessmentAction::class)->execute($registration->id);

            expect($result['assessment'])->toBeNull()
                ->and($result['rubric'])->toBeNull();
        });
    });

    describe('ScoreIndicatorAction', function () {
        it('scores an indicator by authorized evaluator', function () {
            $superAdmin = User::factory()->create();
            $superAdmin->assignRole(Role::SUPER_ADMIN->value);
            $this->actingAs($superAdmin);

            $rubric = Rubric::factory()->create(['is_active' => true]);
            $competency = Competency::factory()->create([
                'rubric_id' => $rubric->id,
                'evaluator_role' => EvaluatorRole::TEACHER,
                'weight' => 100,
            ]);
            $indicator = Indicator::factory()->create([
                'competency_id' => $competency->id,
                'max_score' => 100,
                'weight' => 100,
            ]);
            $internship = Internship::factory()->create();
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create([
                'internship_id' => $internship->id,
                'mentee_id' => $mentee->id,
            ]);
            $assessment = Assessment::factory()->create([
                'registration_id' => $registration->id,
                'rubric_id' => $rubric->id,
                'finalized_at' => null,
            ]);

            $result = app(ScoreIndicatorAction::class)->execute($assessment, $indicator->id, 85.0, $superAdmin);

            $content = $result->content;
            expect($content['competencies'][$competency->id]['indicators'][$indicator->id])->toEqual(85.0);
        });

        it('throws for finalized assessment', function () {
            $user = User::factory()->create();
            $user->assignRole(Role::SUPER_ADMIN->value);
            $assessment = Assessment::factory()->create(['finalized_at' => now()]);

            app(ScoreIndicatorAction::class)->execute($assessment, 'any-indicator', 50, $user);
        })->throws(RejectedException::class);
    });

    describe('UpdateAssessmentScoresAction', function () {
        it('updates an indicator score', function () {
            $assessment = Assessment::factory()->create(['content' => []]);

            $result = app(UpdateAssessmentScoresAction::class)->execute(
                $assessment,
                'competency-1',
                'indicator-1',
                75.0,
            );

            $content = $result->content;
            expect($content['competencies']['competency-1']['indicators']['indicator-1'])->toEqual(75.0);
        });

        it('removes score when null', function () {
            $assessment = Assessment::factory()->create([
                'content' => ['competencies' => ['comp-1' => ['indicators' => ['ind-1' => 80.0]]]],
            ]);

            $result = app(UpdateAssessmentScoresAction::class)->execute($assessment, 'comp-1', 'ind-1', null);

            $content = $result->content;
            expect($content['competencies']['comp-1']['indicators'])->not->toHaveKey('ind-1');
        });
    });

    describe('AutoCalculateAssessmentAction', function () {
        it('skips calculation when finalized', function () {
            $assessment = Assessment::factory()->create(['finalized_at' => now()]);

            $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);

            expect($result->id)->toBe($assessment->id);
        });

        it('calculates and stores auto scores', function () {
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
            $assessment = Assessment::factory()->create([
                'registration_id' => $registration->id,
                'finalized_at' => null,
            ]);

            $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);

            $content = $result->content;
            expect($content)->toHaveKey('auto')
                ->and($content['auto'])->toHaveKeys(['avg_submission_score', 'logbook_completeness', 'report_score']);
        });
    });

    describe('FinalizeAssessmentAction', function () {
        it('finalizes an assessment with scores', function () {
            $superAdmin = User::factory()->create();
            $superAdmin->assignRole(Role::SUPER_ADMIN->value);
            $this->actingAs($superAdmin);

            $rubric = Rubric::factory()->create(['is_active' => true]);
            $competency = Competency::factory()->create([
                'rubric_id' => $rubric->id,
                'evaluator_role' => EvaluatorRole::TEACHER,
                'weight' => 100,
            ]);
            $indicator = Indicator::factory()->create([
                'competency_id' => $competency->id,
                'max_score' => 100,
                'weight' => 100,
            ]);

            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
            $assessment = Assessment::factory()->create([
                'registration_id' => $registration->id,
                'rubric_id' => $rubric->id,
                'finalized_at' => null,
                'content' => [
                    'competencies' => [
                        $competency->id => [
                            'indicators' => [$indicator->id => 80],
                        ],
                    ],
                ],
            ]);

            $result = app(FinalizeAssessmentAction::class)->execute($assessment, $superAdmin);

            expect($result->finalized_at)->not->toBeNull()
                ->and($result->score)->not->toBeNull()
                ->and($result->evaluator_id)->toBe($superAdmin->id);
        });

        it('throws when already finalized', function () {
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $assessment = Assessment::factory()->create(['finalized_at' => now()]);

            app(FinalizeAssessmentAction::class)->execute($assessment, $admin);
        })->throws(RejectedException::class);

        it('throws when no rubric attached', function () {
            $admin = User::factory()->create();
            $admin->assignRole(Role::SUPER_ADMIN->value);
            $assessment = Assessment::factory()->create([
                'rubric_id' => null,
                'finalized_at' => null,
            ]);

            app(FinalizeAssessmentAction::class)->execute($assessment, $admin);
        })->throws(RejectedException::class);
    });

    describe('SchedulePresentationAction', function () {
        it('creates a presentation with examiners', function () {
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
            $examiner = User::factory()->create();

            $presentation = app(SchedulePresentationAction::class)->execute([
                'registration_id' => $registration->id,
                'scheduled_at' => now()->addDays(7)->toDateTimeString(),
                'location' => 'Room 101',
                'examiner_ids' => [$examiner->id],
            ]);

            expect($presentation)->toBeInstanceOf(Presentation::class)
                ->and($presentation->registration_id)->toBe($registration->id)
                ->and($presentation->examiners)->toHaveCount(1)
                ->and($presentation->examiners->first()->examiner_id)->toBe($examiner->id);
        });
    });

    describe('ScorePresentationAction', function () {
        it('scores a presentation examiner entry', function () {
            $examiner = PresentationExaminer::factory()->create(['score' => null, 'feedback' => null]);

            $result = app(ScorePresentationAction::class)->execute($examiner, [
                'score' => 88.5,
                'feedback' => 'Excellent presentation',
            ]);

            expect($result->score)->toBe(88.5)
                ->and($result->feedback)->toBe('Excellent presentation');
        });
    });

    describe('CompletePresentationAction', function () {
        it('completes a presentation with score', function () {
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
            $presentation = Presentation::factory()->create([
                'registration_id' => $registration->id,
                'status' => 'scheduled',
            ]);
            $examiner = User::factory()->create();
            PresentationExaminer::factory()->create([
                'presentation_id' => $presentation->id,
                'examiner_id' => $examiner->id,
                'score' => 90,
            ]);

            $result = app(CompletePresentationAction::class)->execute($presentation);

            expect($result->status->value)->toBe('completed')
                ->and($result->completed_at)->not->toBeNull()
                ->and($result->presentation_score)->toBe(90.0);
        });

        it('calculates final score with report', function () {
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
            Report::factory()->create([
                'registration_id' => $registration->id,
                'status' => 'approved',
                'score' => 80,
            ]);
            $presentation = Presentation::factory()->create([
                'registration_id' => $registration->id,
                'status' => 'scheduled',
            ]);
            $examiner = User::factory()->create();
            PresentationExaminer::factory()->create([
                'presentation_id' => $presentation->id,
                'examiner_id' => $examiner->id,
                'score' => 90,
            ]);

            $result = app(CompletePresentationAction::class)->execute($presentation, reportWeight: 50, presentationWeight: 50);

            expect($result->final_score)->toBe(85.0);
        });
    });
});
