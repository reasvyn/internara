<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Actions\SendAnnouncementNotificationsAction;
use App\SysAdmin\Announcement\Jobs\SendAnnouncementJob;
use App\SysAdmin\Announcement\Models\Announcement;
use App\SysAdmin\Announcement\Notifications\AnnouncementNotification;
use App\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Notification::fake();
});

test('8FVZA-FR-JOB1/JOB2/JOB3: SendAnnouncementJob is a queued job with tries and backoff', function () {
    $job = new SendAnnouncementJob(announcementId: 'announcement-id');

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([2, 10, 30]);
});

test('3S55V-FR-A5: handle sends the announcement notification to targeted roles', function () {
    $sender = User::factory()->create();
    $sender->assignRole('admin');
    $this->actingAs($sender);

    $teacher1 = User::factory()->create();
    $teacher1->assignRole('teacher');
    $teacher2 = User::factory()->create();
    $teacher2->assignRole('teacher');
    $student = User::factory()->create();
    $student->assignRole('student');

    $announcement = Announcement::factory()->create([
        'title' => 'Pra-PKL Meeting',
        'message' => 'Mandatory attendance.',
        'target_roles' => ['teacher'],
        'created_by' => $sender->id,
    ]);

    $job = new SendAnnouncementJob(announcementId: $announcement->id);
    $job->handle(app(SendAnnouncementNotificationsAction::class));

    Notification::assertSentTo(
        [$teacher1, $teacher2],
        AnnouncementNotification::class,
        function (AnnouncementNotification $notification) use ($announcement) {
            return $notification->title === $announcement->title
                && $notification->message === $announcement->message;
        },
    );

    Notification::assertNotSentTo($student, AnnouncementNotification::class);
});

test('3S55V-UC-*: handle with empty target_roles broadcasts to all users', function () {
    $sender = User::factory()->create();
    $sender->assignRole('admin');
    $this->actingAs($sender);

    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    $announcement = Announcement::factory()->create([
        'target_roles' => null,
        'created_by' => $sender->id,
    ]);

    $job = new SendAnnouncementJob(announcementId: $announcement->id);
    $job->handle(app(SendAnnouncementNotificationsAction::class));

    Notification::assertSentTo([$teacher, $supervisor], AnnouncementNotification::class);
});

test('8FVZA-UC-1: missing announcement throws during handle', function () {
    $job = new SendAnnouncementJob(announcementId: 'missing-announcement');

    expect(fn () => $job->handle(app(SendAnnouncementNotificationsAction::class)))
        ->toThrow(ModelNotFoundException::class);
});

test('8FVZA-NFR-JOB4: failed() logs the send error with announcement id context', function () {
    $logs = captureLogs();

    $job = new SendAnnouncementJob(announcementId: 'announcement-1');

    $job->failed(new RuntimeException('mail server down'));

    $log = $logs->last();
    expect($log->level)->toBe('error')
        ->and($log->message)->toBe('Announcement sending failed')
        ->and($log->context['announcement_id'])->toBe('announcement-1')
        ->and($log->context['error'])->toBe('mail server down');
});

test('8FVZA-FR-JOB5: dispatch pushes the job to the queue', function () {
    $announcement = Announcement::factory()->create();

    SendAnnouncementJob::dispatch(announcementId: $announcement->id);

    Queue::assertPushed(SendAnnouncementJob::class);
});
