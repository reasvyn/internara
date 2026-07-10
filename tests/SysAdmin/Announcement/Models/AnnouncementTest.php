<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Entities\AnnouncementState;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Models\Announcement;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('factory creates a valid announcement', function () {
    $announcement = Announcement::factory()->create();

    expect($announcement)->toBeInstanceOf(Announcement::class);
    expect($announcement->title)->not->toBeNull();
    expect($announcement->message)->not->toBeNull();
});

test('belongs to a creator', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->create(['created_by' => $user->id]);

    expect($announcement->creator)->toBeInstanceOf(User::class);
    expect($announcement->creator->id)->toBe($user->id);
});

test('scope published returns only published announcements', function () {
    Announcement::factory()->create(['status' => AnnouncementStatus::PUBLISHED]);
    Announcement::factory()->create(['status' => AnnouncementStatus::DRAFT]);

    expect(Announcement::published()->count())->toBe(1);
});

test('scope draft returns only draft announcements', function () {
    Announcement::factory()->create(['status' => AnnouncementStatus::PUBLISHED]);
    Announcement::factory()->create(['status' => AnnouncementStatus::DRAFT]);

    expect(Announcement::draft()->count())->toBe(1);
});

test('scope scheduled returns only scheduled announcements', function () {
    Announcement::factory()->create(['status' => AnnouncementStatus::SCHEDULED]);
    Announcement::factory()->create(['status' => AnnouncementStatus::DRAFT]);

    expect(Announcement::scheduled()->count())->toBe(1);
});

test('scope pending publish returns scheduled announcements past their scheduled time', function () {
    Announcement::factory()->create([
        'status' => AnnouncementStatus::SCHEDULED,
        'scheduled_at' => now()->subHour(),
    ]);
    Announcement::factory()->create([
        'status' => AnnouncementStatus::SCHEDULED,
        'scheduled_at' => now()->addHour(),
    ]);

    expect(Announcement::pendingPublish()->count())->toBe(1);
});

test('as announcement state returns state entity', function () {
    $announcement = Announcement::factory()->create(['status' => AnnouncementStatus::PUBLISHED]);

    $state = $announcement->asAnnouncementState();

    expect($state)->toBeInstanceOf(AnnouncementState::class);
    expect($state->isPublished())->toBeTrue();
});

test('casts status to enum', function () {
    $announcement = Announcement::factory()->create(['status' => AnnouncementStatus::PUBLISHED]);

    expect($announcement->status)->toBeInstanceOf(AnnouncementStatus::class);
});
