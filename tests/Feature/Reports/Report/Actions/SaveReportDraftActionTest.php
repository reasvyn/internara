<?php

declare(strict_types=1);

use App\Reports\Report\Actions\SaveReportDraftAction;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('save report draft action updates report content', function () {
    $report = Report::factory()->create(['status' => ReportStatus::DRAFT]);
    $content = ['Chapter 1' => 'Draft content', 'Chapter 2' => 'More content'];

    $updated = app(SaveReportDraftAction::class)->execute($report, $content);

    expect($updated->content)->toBe($content);
    expect($updated->id)->toBe($report->id);
});

test('save report draft action can overwrite existing content', function () {
    $report = Report::factory()->create([
        'status' => ReportStatus::DRAFT,
        'content' => ['old' => 'content'],
    ]);

    $updated = app(SaveReportDraftAction::class)->execute($report, ['new' => 'content']);

    expect($updated->content)->toBe(['new' => 'content']);
});
