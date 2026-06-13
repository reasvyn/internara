<?php

declare(strict_types=1);

use App\Reports\Report\Actions\AddSupervisorReportNotesAction;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('add supervisor report notes action adds notes to a report', function () {
    $report = Report::factory()->create(['status' => ReportStatus::SUBMITTED]);

    $updated = app(AddSupervisorReportNotesAction::class)->execute($report, 'Please improve the methodology section.');

    expect($updated->supervisor_notes)->toBe('Please improve the methodology section.');
});

test('add supervisor report notes action overwrites existing notes', function () {
    $report = Report::factory()->create([
        'status' => ReportStatus::SUBMITTED,
        'supervisor_notes' => 'Old notes',
    ]);

    $updated = app(AddSupervisorReportNotesAction::class)->execute($report, 'New notes');

    expect($updated->supervisor_notes)->toBe('New notes');
});

test('add supervisor report notes action validates notes is required', function () {
    $report = Report::factory()->create(['status' => ReportStatus::SUBMITTED]);

    app(AddSupervisorReportNotesAction::class)->execute($report, '');
})->throws(ValidationException::class);

test('add supervisor report notes action validates notes max length', function () {
    $report = Report::factory()->create(['status' => ReportStatus::SUBMITTED]);

    app(AddSupervisorReportNotesAction::class)->execute($report, str_repeat('A', 5001));
})->throws(ValidationException::class);
