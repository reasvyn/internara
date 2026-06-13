<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Reports\Report\Actions\RequestReportRevisionAction;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('request report revision action creates revision record and updates report status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $report = Report::factory()->create(['status' => ReportStatus::SUBMITTED]);

    $updated = app(RequestReportRevisionAction::class)->execute($report, 'Please revise chapter 3.');

    expect($updated->status->value)->toBe('revision_required');

    $revision = $updated->revisions()->first();
    expect($revision)->not->toBeNull();
    expect($revision->round)->toBe(1);
    expect($revision->feedback)->toBe('Please revise chapter 3.');
    expect($revision->requested_by)->toBe($user->id);
});

test('request report revision action increments round number', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $report = Report::factory()->create(['status' => ReportStatus::SUBMITTED]);

    app(RequestReportRevisionAction::class)->execute($report, 'First revision');
    $second = app(RequestReportRevisionAction::class)->execute($report, 'Second revision');

    expect($second->revisions()->count())->toBe(2);
    expect($second->revisions()->max('round'))->toBe(2);
});

test('request report revision action cannot revise a finalized report', function () {
    $this->actingAs(User::factory()->create());

    $report = Report::factory()->create(['status' => ReportStatus::FINALIZED]);

    app(RequestReportRevisionAction::class)->execute($report, 'Feedback');
})->throws(RejectedException::class, 'This report has already been approved.');
