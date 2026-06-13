<?php

declare(strict_types=1);

use App\Reports\Report\Models\Report;
use App\Reports\Report\Models\ReportRevision;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

test('report revision factory creates valid model', function () {
    $revision = ReportRevision::factory()->create();

    expect($revision)->toBeInstanceOf(ReportRevision::class);
    expect($revision->report_id)->not->toBeNull();
    expect($revision->round)->not->toBeNull();
    expect($revision->feedback)->not->toBeNull();
    expect($revision->requested_by)->not->toBeNull();
});

test('report revision belongs to report', function () {
    $report = Report::factory()->create();
    $revision = ReportRevision::factory()->create(['report_id' => $report->id]);

    expect($revision->report)->toBeInstanceOf(Report::class);
    expect($revision->report->id)->toBe($report->id);
});

test('report revision belongs to requester', function () {
    $user = User::factory()->create();
    $revision = ReportRevision::factory()->create(['requested_by' => $user->id]);

    expect($revision->requester)->toBeInstanceOf(User::class);
    expect($revision->requester->id)->toBe($user->id);
});

test('report revision casts requested_at to datetime', function () {
    $revision = ReportRevision::factory()->create(['requested_at' => now()]);

    expect($revision->requested_at)->toBeInstanceOf(Carbon::class);
});

test('report revision casts resubmitted_at to datetime', function () {
    $revision = ReportRevision::factory()->create(['resubmitted_at' => now()]);

    expect($revision->resubmitted_at)->toBeInstanceOf(Carbon::class);
});

test('report revision fillable attributes are mass assignable', function () {
    $report = Report::factory()->create();
    $user = User::factory()->create();

    $revision = ReportRevision::create([
        'report_id' => $report->id,
        'round' => 1,
        'feedback' => 'Revise introduction.',
        'requested_by' => $user->id,
        'requested_at' => now(),
    ]);

    expect($revision->report_id)->toBe($report->id);
    expect($revision->round)->toBe(1);
    expect($revision->feedback)->toBe('Revise introduction.');
    expect($revision->requested_by)->toBe($user->id);
});
