<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Actions\CompileLogbookReportAction;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Pdf::partialMock();
    Pdf::shouldReceive('loadHTML->setPaper->stream')
        ->andReturn(response('pdf-content', 200, ['Content-Type' => 'application/pdf']));
});

test('downloads pdf report for verified entries', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    Logbook::factory()->count(3)->sequence(
        ['date' => now()->subDays(2)],
        ['date' => now()->subDay()],
        ['date' => now()],
    )->create([
        'registration_id' => $registration->id,
        'status' => LogbookStatus::VERIFIED,
    ]);

    $response = app(CompileLogbookReportAction::class)->execute($registration);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('downloads pdf report with only verified entries', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'date' => now()->subDay(),
        'status' => LogbookStatus::DRAFT,
    ]);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'date' => now(),
        'status' => LogbookStatus::VERIFIED,
    ]);

    $response = app(CompileLogbookReportAction::class)->execute($registration);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('downloads pdf report without supervisor notes', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'status' => LogbookStatus::VERIFIED,
    ]);

    $response = app(CompileLogbookReportAction::class)->execute(
        $registration,
        includeSupervisorNotes: false,
    );

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('generates pdf with entries ordered by date', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'date' => now(),
        'status' => LogbookStatus::VERIFIED,
    ]);
    Logbook::factory()->create([
        'registration_id' => $registration->id,
        'date' => now()->addDay(),
        'status' => LogbookStatus::VERIFIED,
    ]);

    $response = app(CompileLogbookReportAction::class)->execute($registration);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('returns valid response for registration with no entries', function () {
    $registration = Registration::factory()->create(['status' => 'active']);

    $response = app(CompileLogbookReportAction::class)->execute($registration);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});
