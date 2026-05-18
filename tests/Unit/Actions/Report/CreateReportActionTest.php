<?php

declare(strict_types=1);

use App\Actions\Report\ApproveReportAction;
use App\Actions\Report\CreateReportAction;
use App\Actions\Report\SubmitReportAction;
use App\Models\Registration;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Report workflow', function () {
    it('creates a report', function () {
        $registration = Registration::factory()->create(['status' => 'active']);

        $report = app(CreateReportAction::class)->execute([
            'registration_id' => $registration->id,
            'title' => 'My PKL Report',
        ]);

        expect($report)->toBeInstanceOf(Report::class)
            ->and($report->status->value)->toBe('draft');
    });

    it('submits a report', function () {
        $registration = Registration::factory()->create(['status' => 'active']);

        $report = app(CreateReportAction::class)->execute([
            'registration_id' => $registration->id,
            'title' => 'My Report',
        ]);

        $submitted = app(SubmitReportAction::class)->execute($report, [
            'chapter_1' => 'Introduction content',
            'chapter_2' => 'Company profile',
        ]);

        expect($submitted->status->value)->toBe('submitted')
            ->and($submitted->submitted_at)->not->toBeNull();
    });

    it('approves a report with score', function () {
        $registration = Registration::factory()->create(['status' => 'active']);

        $report = app(CreateReportAction::class)->execute([
            'registration_id' => $registration->id,
            'title' => 'My Report',
        ]);

        app(SubmitReportAction::class)->execute($report, ['chapter_1' => 'Content']);

        $approved = app(ApproveReportAction::class)->execute(
            Report::where('registration_id', $registration->id)->first(),
            ['score' => 85, 'feedback' => 'Great work!']
        );

        expect($approved->status->value)->toBe('approved')
            ->and($approved->score)->toBe(85.0)
            ->and($approved->feedback)->toBe('Great work!');
    });
});
