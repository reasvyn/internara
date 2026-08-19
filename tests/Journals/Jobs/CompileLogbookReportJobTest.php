<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Jobs\CompileLogbookReportJob;
use App\Journals\Logbook\Actions\CompileLogbookReportAction;
use App\Journals\Logbook\Models\Logbook;
use App\Program\Internship\Models\Internship;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use App\User\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('8FVZA-FR-JOB1/JOB2/JOB3: CompileLogbookReportJob is a queued job with tries and backoff', function () {
    $job = new CompileLogbookReportJob(studentId: 'student-id', internshipId: 'internship-id');

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([2, 10, 30]);
});

test('1KSWL-FR-LB8: handle compiles the logbook report for the matching registration', function () {
    $student = User::factory()->create(['name' => 'John Doe']);
    $internship = Internship::factory()->create();
    $registration = Registration::factory()->active()->create([
        'student_id' => $student->id,
        'internship_id' => $internship->id,
    ]);

    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'user_id' => $student->id,
    ]);

    Logbook::factory()->verified()->create([
        'user_id' => $student->id,
        'registration_id' => $registration->id,
        'date' => '2026-08-18',
    ]);

    Pdf::shouldReceive('loadHTML')->once()->andReturnSelf();
    Pdf::shouldReceive('setPaper')->once()->andReturnSelf();
    Pdf::shouldReceive('stream')->once()
        ->with(Mockery::on(fn ($filename) => str_contains($filename, 'John Doe')))
        ->andReturn(new Response('pdf', 200));

    $job = new CompileLogbookReportJob(studentId: $student->id, internshipId: $internship->id);

    $job->handle(app(CompileLogbookReportAction::class));
});

test('8FVZA-UC-1: missing registration throws during handle', function () {
    $job = new CompileLogbookReportJob(studentId: 'no-student', internshipId: 'no-internship');

    expect(fn () => $job->handle(app(CompileLogbookReportAction::class)))
        ->toThrow(ModelNotFoundException::class);
});

test('8FVZA-NFR-JOB4: failed() logs the compile error with student/internship context', function () {
    $logs = captureLogs();

    $job = new CompileLogbookReportJob(studentId: 'student-1', internshipId: 'internship-2');

    $job->failed(new RuntimeException('template missing'));

    $log = $logs->last();
    expect($log->level)->toBe('error')
        ->and($log->message)->toBe('Logbook report compilation failed')
        ->and($log->context['student_id'])->toBe('student-1')
        ->and($log->context['internship_id'])->toBe('internship-2')
        ->and($log->context['error'])->toBe('template missing');
});

test('8FVZA-FR-JOB5: dispatch pushes the job to the queue', function () {
    CompileLogbookReportJob::dispatch(studentId: 'student-1', internshipId: 'internship-2');

    Queue::assertPushed(CompileLogbookReportJob::class);
});
