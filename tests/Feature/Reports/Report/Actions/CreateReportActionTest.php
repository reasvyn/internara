<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Reports\Report\Actions\CreateReportAction;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('create report action creates a report with valid data', function () {
    $registration = Registration::factory()->create();

    $report = app(CreateReportAction::class)->execute([
        'registration_id' => $registration->id,
        'title' => 'My Internship Report',
        'chapter_structure' => ['Introduction', 'Methods', 'Results'],
    ]);

    expect($report)->toBeInstanceOf(Report::class);
    expect($report->exists)->toBeTrue();
    expect($report->registration_id)->toBe($registration->id);
    expect($report->title)->toBe('My Internship Report');
    expect($report->chapter_structure)->toBe(['Introduction', 'Methods', 'Results']);
    expect($report->status)->toBeInstanceOf(ReportStatus::class);
    expect($report->status->value)->toBe('draft');
});

test('create report action creates a report without chapter structure', function () {
    $registration = Registration::factory()->create();

    $report = app(CreateReportAction::class)->execute([
        'registration_id' => $registration->id,
        'title' => 'Simple Report',
    ]);

    expect($report->chapter_structure)->toBeNull();
});

test('create report action validates required fields', function () {
    app(CreateReportAction::class)->execute([]);
})->throws(ValidationException::class);

test('create report action validates registration exists', function () {
    app(CreateReportAction::class)->execute([
        'registration_id' => 'non-existent-id',
        'title' => 'Test',
    ]);
})->throws(ValidationException::class);

test('create report action validates title max length', function () {
    $registration = Registration::factory()->create();

    app(CreateReportAction::class)->execute([
        'registration_id' => $registration->id,
        'title' => str_repeat('A', 256),
    ]);
})->throws(ValidationException::class);
