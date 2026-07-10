<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

test('report factory creates valid model', function () {
    $report = Report::factory()->create();

    expect($report)->toBeInstanceOf(Report::class);
    expect($report->registration_id)->not->toBeNull();
    expect($report->supervisor_score)->not->toBeNull();
    expect($report->teacher_score)->not->toBeNull();
    expect($report->exam_score)->not->toBeNull();
    expect($report->final_score)->not->toBeNull();
    expect($report->grade_letter)->not->toBeNull();
});

test('report defaults to draft status', function () {
    $report = new Report;

    expect($report->status)->toBe(ReportStatus::DRAFT);
});

test('report casts status to enum', function () {
    $report = Report::factory()->create(['status' => ReportStatus::DRAFT]);

    expect($report->status)->toBeInstanceOf(ReportStatus::class);
    expect($report->status)->toBe(ReportStatus::DRAFT);
});

test('report casts scores to float', function () {
    $report = Report::factory()->create([
        'supervisor_score' => 85.5,
        'teacher_score' => 90.0,
        'exam_score' => 78.3,
        'final_score' => 84.6,
    ]);

    expect($report->supervisor_score)->toBeFloat();
    expect($report->teacher_score)->toBeFloat();
    expect($report->exam_score)->toBeFloat();
    expect($report->final_score)->toBeFloat();
});

test('report casts finalized_at to datetime', function () {
    $report = Report::factory()->create(['finalized_at' => now()]);

    expect($report->finalized_at)->toBeInstanceOf(Carbon::class);
});

test('report casts archived_data to array', function () {
    $data = ['school_name' => 'Test School', 'finalized_by_name' => 'Admin'];
    $report = Report::factory()->create(['archived_data' => $data]);

    expect($report->archived_data)->toBeArray();
    expect($report->archived_data)->toHaveKey('school_name');
});

test('report belongs to registration', function () {
    $registration = Registration::factory()->create();
    $report = Report::factory()->create(['registration_id' => $registration->id]);

    expect($report->registration)->toBeInstanceOf(Registration::class);
    expect($report->registration->id)->toBe($registration->id);
});

test('report belongs to finalizedBy', function () {
    $user = User::factory()->create();
    $report = Report::factory()->create(['finalized_by' => $user->id]);

    expect($report->finalizedBy)->toBeInstanceOf(User::class);
    expect($report->finalizedBy->id)->toBe($user->id);
});

test('report fillable attributes are mass assignable', function () {
    $registration = Registration::factory()->create();
    $user = User::factory()->create();

    $report = Report::create([
        'registration_id' => $registration->id,
        'supervisor_score' => 90.0,
        'teacher_score' => 85.0,
        'exam_score' => 88.0,
        'final_score' => 87.67,
        'grade_letter' => 'A',
        'industry_feedback' => 'Great job!',
        'status' => 'draft',
        'finalized_by' => $user->id,
        'finalized_at' => now(),
        'archived_data' => ['key' => 'value'],
    ]);

    expect($report->registration_id)->toBe($registration->id);
    expect($report->supervisor_score)->toBe(90.0);
    expect($report->grade_letter)->toBe('A');
    expect($report->industry_feedback)->toBe('Great job!');
});

test('report captures snapshot on finalization', function () {
    $registration = Registration::factory()->create();
    $existing = Report::factory()->create();

    $report = Report::factory()->create([
        'registration_id' => $registration->id,
        'status' => ReportStatus::DRAFT,
    ]);

    $report = $report->fresh();
    $report->captureSnapshot();
    $report->saveQuietly();
    $report->refresh();

    expect($report->archived_data)->toBeArray();
    expect($report->archived_data)->toHaveKey('captured_at');
});
