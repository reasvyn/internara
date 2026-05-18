<?php

declare(strict_types=1);

use App\Actions\Report\AddSupervisorReportNotesAction;
use App\Models\Registration;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds supervisor notes to a report', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $report = Report::create([
        'registration_id' => $registration->id,
        'title' => 'Test Report',
    ]);

    $result = app(AddSupervisorReportNotesAction::class)->execute($report, 'Student showed great initiative.');

    expect($result->supervisor_notes)->toBe('Student showed great initiative.');
});

it('overwrites existing supervisor notes', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $report = Report::create([
        'registration_id' => $registration->id,
        'title' => 'Test Report',
        'supervisor_notes' => 'Old notes',
    ]);

    $result = app(AddSupervisorReportNotesAction::class)->execute($report, 'Updated notes.');

    expect($result->supervisor_notes)->toBe('Updated notes.');
});
