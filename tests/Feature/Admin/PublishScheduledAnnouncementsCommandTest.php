<?php

declare(strict_types=1);

use App\Domain\Admin\Enums\AnnouncementStatus;
use App\Domain\Admin\Models\Announcement;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification as NotificationFacade;

beforeEach(function () {
    app()->setLocale('en');
    NotificationFacade::fake();
});

describe('PublishScheduledAnnouncementsCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('announcements:publish');
    });

    it('reports no scheduled announcements when none due', function () {
        $this->artisan('announcements:publish')
            ->assertExitCode(0)
            ->expectsOutputToContain('No scheduled announcements');
    });

    it('publishes due scheduled announcements', function () {
        $user = User::factory()->create();

        Announcement::factory()->create([
            'status' => AnnouncementStatus::SCHEDULED,
            'scheduled_at' => now()->subHour(),
            'created_by' => $user->id,
        ]);

        $this->artisan('announcements:publish')
            ->assertExitCode(0)
            ->expectsOutputToContain('Published 1');
    });

    it('does not publish future scheduled announcements', function () {
        $user = User::factory()->create();

        Announcement::factory()->create([
            'status' => AnnouncementStatus::SCHEDULED,
            'scheduled_at' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        $this->artisan('announcements:publish')
            ->assertExitCode(0)
            ->expectsOutputToContain('No scheduled announcements');
    });

    it('publishes multiple due announcements', function () {
        $user = User::factory()->create();

        Announcement::factory()->count(3)->create([
            'status' => AnnouncementStatus::SCHEDULED,
            'scheduled_at' => now()->subHour(),
            'created_by' => $user->id,
        ]);

        $this->artisan('announcements:publish')
            ->assertExitCode(0)
            ->expectsOutputToContain('Published 3');
    });
});
