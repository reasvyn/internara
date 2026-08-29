<?php

declare(strict_types=1);

use App\Modules\User\Domain\UserManagement\Actions\SetUserStatusAction;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Jobs\ArchiveStudentAccountsJob;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('8FVZA-FR-JOB1/JOB2/JOB3: ArchiveStudentAccountsJob is a queued job with tries and backoff [2, 10, 30]', function () {
    $job = new ArchiveStudentAccountsJob(studentIds: ['student-a']);

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([2, 10, 30]);
});

test('E1MSJ-FR-AS7: handle archives each eligible student via SetUserStatusAction', function () {
    $student1 = User::factory()->create();
    $student2 = User::factory()->create();
    $student1->assignRole('student');
    $student2->assignRole('student');

    $job = new ArchiveStudentAccountsJob(studentIds: [$student1->id, $student2->id]);

    $job->handle(app(SetUserStatusAction::class));

    expect(User::find($student1->id)->status)->toBe(AccountStatus::ARCHIVED)
        ->and(User::find($student2->id)->status)->toBe(AccountStatus::ARCHIVED);
});

test('E1MSJ-FR-AS2: handle skips users who do not match the id list', function () {
    $target = User::factory()->create();
    $target->assignRole('student');
    $untouched = User::factory()->create();
    $untouched->assignRole('student');

    $job = new ArchiveStudentAccountsJob(studentIds: [$target->id]);

    $job->handle(app(SetUserStatusAction::class));

    expect(User::find($target->id)->status)->toBe(AccountStatus::ARCHIVED)
        ->and(User::find($untouched->id)->status)->toBe(AccountStatus::ACTIVATED);
});

test('8FVZA-NFR-JOB4: failed() logs the archive error with student ids context', function () {
    $logs = captureLogs();

    $job = new ArchiveStudentAccountsJob(studentIds: ['student-a', 'student-b']);

    $job->failed(new RuntimeException('db connection lost'));

    $log = $logs->last();
    expect($log->level)->toBe('error')
        ->and($log->message)->toBe('Batch student archiving failed')
        ->and($log->context['student_ids'])->toBe(['student-a', 'student-b'])
        ->and($log->context['error'])->toBe('db connection lost');
});

test('8FVZA-FR-JOB5: dispatch pushes the job to the queue with minimal payload', function () {
    ArchiveStudentAccountsJob::dispatch(studentIds: ['student-a']);

    Queue::assertPushed(ArchiveStudentAccountsJob::class);
});
