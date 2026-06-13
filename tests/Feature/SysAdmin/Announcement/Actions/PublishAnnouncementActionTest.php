<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Actions\PublishAnnouncementAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Models\Announcement;
use App\SysAdmin\Announcement\Notifications\AnnouncementNotification;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('publishes a draft announcement', function () {
    $announcement = Announcement::factory()->create(['status' => AnnouncementStatus::DRAFT]);
    Notification::fake();

    app(PublishAnnouncementAction::class)->execute($announcement);

    $announcement->refresh();
    expect($announcement->status)->toBe(AnnouncementStatus::PUBLISHED);
    expect($announcement->scheduled_at)->toBeNull();
});

test('sends notification to all users when no target roles', function () {
    $announcement = Announcement::factory()->create(['status' => AnnouncementStatus::DRAFT, 'target_roles' => null]);
    User::factory()->count(3)->create();
    Notification::fake();

    app(PublishAnnouncementAction::class)->execute($announcement);

    Notification::assertSentTo(User::all(), AnnouncementNotification::class);
});

test('sends notification to targeted roles only', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($admin);

    $announcement = Announcement::factory()->create([
        'status' => AnnouncementStatus::DRAFT,
        'target_roles' => ['student'],
        'created_by' => $admin->id,
    ]);
    Notification::fake();

    app(PublishAnnouncementAction::class)->execute($announcement);

    Notification::assertSentTo($student, AnnouncementNotification::class);
    Notification::assertNotSentTo($admin, AnnouncementNotification::class);
});
