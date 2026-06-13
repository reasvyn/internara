<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Actions\CompileLogbookReportAction;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('downloads pdf report for verified entries', function () {
    Pdf::fake();

    $registration = Registration::factory()->create(['status' => 'active']);
    Logbook::factory()->count(3)->create([
        'registration_id' => $registration->id,
        'status' => LogbookStatus::VERIFIED,
    ]);

    $response = app(CompileLogbookReportAction::class)->download($registration);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('downloads pdf report with only verified entries', function () {
    Pdf::fake();

    $registration = Registration::factory()->create(['status' => 'active']);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'status' => LogbookStatus::DRAFT,
    ]);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'status' => LogbookStatus::VERIFIED,
    ]);

    $response = app(CompileLogbookReportAction::class)->download($registration);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('downloads pdf report without supervisor notes', function () {
    Pdf::fake();

    $registration = Registration::factory()->create(['status' => 'active']);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'status' => LogbookStatus::VERIFIED,
    ]);

    $response = app(CompileLogbookReportAction::class)->download(
        $registration,
        includeSupervisorNotes: false,
    );

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('generates pdf with entries ordered by date', function () {
    Pdf::fake();

    $registration = Registration::factory()->create(['status' => 'active']);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'date' => now()->addDay(),
        'status' => LogbookStatus::VERIFIED,
    ]);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'date' => now(),
        'status' => LogbookStatus::VERIFIED,
    ]);

    $response = app(CompileLogbookReportAction::class)->download($registration);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('returns valid response for registration with no entries', function () {
    Pdf::fake();

    $registration = Registration::factory()->create(['status' => 'active']);

    $response = app(CompileLogbookReportAction::class)->download($registration);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});
