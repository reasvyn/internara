<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Reports\Report\Actions\SubmitReportAction;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('submit report action submits a draft report', function () {
    $report = Report::factory()->create(['status' => ReportStatus::DRAFT]);
    $content = ['Introduction' => 'Hello world', 'Methods' => 'We did stuff'];

    $submitted = app(SubmitReportAction::class)->execute($report, $content);

    expect($submitted)->toBeInstanceOf(Report::class);
    expect($submitted->status->value)->toBe('submitted');
    expect($submitted->content)->toBe($content);
    expect($submitted->submitted_at)->not->toBeNull();
});

test('submit report action cannot submit a finalized report', function () {
    $report = Report::factory()->create(['status' => ReportStatus::FINALIZED]);

    app(SubmitReportAction::class)->execute($report, ['content' => 'test']);
})->throws(RejectedException::class, 'This report has already been approved.');
