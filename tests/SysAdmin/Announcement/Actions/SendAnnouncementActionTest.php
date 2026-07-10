<?php

declare(strict_types=1);

use App\Auth\Permissions\Enums\Role;
use App\SysAdmin\Announcement\Actions\SendAnnouncementAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Models\Announcement;
use App\SysAdmin\Announcement\Notifications\AnnouncementNotification;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::SUPER_ADMIN->value);
    test()->actingAs($admin);
});

test('creates a draft announcement by default', function () {
    $announcement = app(SendAnnouncementAction::class)->execute([
        'title' => 'Test Title',
        'message' => 'Test message content',
        'type' => 'info',
    ]);

    expect($announcement)->toBeInstanceOf(Announcement::class);
    expect($announcement->status)->toBe(AnnouncementStatus::DRAFT);
    expect($announcement->type)->toBe('info');
});

test('creates and sends a published announcement', function () {
    Notification::fake();

    $announcement = app(SendAnnouncementAction::class)->execute([
        'title' => 'Published Now',
        'message' => 'Sent immediately',
        'type' => 'info',
        'status' => 'published',
    ]);

    expect($announcement->status)->toBe(AnnouncementStatus::PUBLISHED);
    Notification::assertSentTimes(AnnouncementNotification::class, 1);
});

test('creates a scheduled announcement', function () {
    $announcement = app(SendAnnouncementAction::class)->execute([
        'title' => 'Future',
        'message' => 'Scheduled',
        'type' => 'info',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
    ]);

    expect($announcement->status)->toBe(AnnouncementStatus::SCHEDULED);
    expect($announcement->scheduled_at)->not->toBeNull();
});

test('validates required fields', function () {
    expect(fn () => app(SendAnnouncementAction::class)->execute([]))->toThrow(ValidationException::class);
});
