<?php

declare(strict_types=1);

use App\Actions\Assessment\AutoCalculateAssessmentAction;
use App\Models\Logbook;
use Database\Factories\AssessmentFactory;
use Database\Factories\SubmissionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('returns the assessment unchanged if already finalized', function () {
        $assessment = AssessmentFactory::new()->finalized()->create();

        $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);

        expect($result->finalized_at)->not->toBeNull();
    });

    it('calculates average submission score for verified submissions', function () {
        $assessment = AssessmentFactory::new()->create();
        $regId = $assessment->registration_id;

        SubmissionFactory::new()->verified()->create([
            'registration_id' => $regId,
            'score' => 80.0,
        ]);
        SubmissionFactory::new()->verified()->create([
            'registration_id' => $regId,
            'score' => 90.0,
        ]);

        $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);
        $content = $result->content;

        expect($content['auto']['avg_submission_score'])->toBe(85);
    });

    it('ignores non-verified submissions in average', function () {
        $assessment = AssessmentFactory::new()->create();
        $regId = $assessment->registration_id;

        SubmissionFactory::new()->create([
            'registration_id' => $regId,
            'score' => 100,
            'status' => 'submitted',
        ]);
        SubmissionFactory::new()->verified()->create([
            'registration_id' => $regId,
            'score' => 70,
        ]);

        $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);
        $content = $result->content;

        expect($content['auto']['avg_submission_score'])->toBe(70);
    });

    it('sets avg_submission_score to null when no verified submissions with scores', function () {
        $assessment = AssessmentFactory::new()->create();
        $regId = $assessment->registration_id;

        SubmissionFactory::new()->create([
            'registration_id' => $regId,
            'score' => null,
            'status' => 'submitted',
        ]);

        $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);
        $content = $result->content;

        expect($content['auto']['avg_submission_score'])->toBeNull();
    });

    it('calculates logbook completeness percentage', function () {
        $assessment = AssessmentFactory::new()->create();
        $regId = $assessment->registration_id;

        Logbook::factory()->count(3)->create([
            'registration_id' => $regId,
            'status' => 'submitted',
        ]);
        Logbook::factory()->count(2)->create([
            'registration_id' => $regId,
            'status' => 'draft',
        ]);

        $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);
        $content = $result->content;

        expect($content['auto']['logbook_completeness'])->toBe(60);
    });

    it('sets logbook completeness to 0 when no logbooks exist', function () {
        $assessment = AssessmentFactory::new()->create();

        $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);
        $content = $result->content;

        expect($content['auto']['logbook_completeness'])->toBe(0);
    });

    it('preserves existing content when adding auto data', function () {
        $assessment = AssessmentFactory::new()->create([
            'content' => ['existing_key' => 'existing_value'],
        ]);

        $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);
        $content = $result->content;

        expect($content['existing_key'])->toBe('existing_value')
            ->and($content['auto'])->toBeArray();
    });
});
