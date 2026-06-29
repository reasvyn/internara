<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Reports\Report\Actions\ApproveReportAction;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('approve report action approves a submitted report with score and feedback', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $report = Report::factory()->create(['status' => ReportStatus::SUBMITTED]);

    $approved = app(ApproveReportAction::class)->execute($report, [
        'feedback' => 'Excellent work!',
    ]);

    expect($approved->status->value)->toBe('approved');
    expect($approved->industry_feedback)->toBe('Excellent work!');
});

test('approve report action approves without score and feedback', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $report = Report::factory()->create(['status' => ReportStatus::SUBMITTED]);

    $approved = app(ApproveReportAction::class)->execute($report, []);

    expect($approved->status->value)->toBe('approved');
    expect($approved->industry_feedback)->toBeNull();
});

test('approve report action cannot approve a finalized report', function () {
    $this->actingAs(User::factory()->create());

    $report = Report::factory()->create(['status' => ReportStatus::FINALIZED]);

    app(ApproveReportAction::class)->execute($report, []);
})->throws(RejectedException::class, 'This report has already been approved.');
