<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Reports\Report\Actions\CreateReportAction;
use App\Reports\Report\Data\CreateReportData;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('create report action creates a report with valid data', function () {
    $registration = Registration::factory()->create();

    $report = app(CreateReportAction::class)->execute(
        new CreateReportData(registrationId: $registration->id),
    );

    expect($report)->toBeInstanceOf(Report::class);
    expect($report->exists)->toBeTrue();
    expect($report->registration_id)->toBe($registration->id);
    expect($report->status)->toBeInstanceOf(ReportStatus::class);
    expect($report->status->value)->toBe('draft');
});
