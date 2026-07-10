<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Models\Announcement;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('publishes scheduled announcements that are due', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);

    $announcement = Announcement::factory()->create([
        'status' => AnnouncementStatus::SCHEDULED,
        'scheduled_at' => now()->subHour(),
        'created_by' => $admin->id,
    ]);

    $this->artisan('announcements:publish')
        ->assertExitCode(0);

    $announcement->refresh();
    expect($announcement->status)->toBe(AnnouncementStatus::PUBLISHED);
});

test('does nothing when no scheduled announcements are due', function () {
    Announcement::factory()->create([
        'status' => AnnouncementStatus::SCHEDULED,
        'scheduled_at' => now()->addDay(),
    ]);

    $this->artisan('announcements:publish')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.publish_announcements.none_found'));
});
