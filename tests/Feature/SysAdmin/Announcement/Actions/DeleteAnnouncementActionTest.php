<?php

declare(strict_types=1);

use App\SysAdmin\Announcement\Actions\DeleteAnnouncementAction;
use App\SysAdmin\Announcement\Models\Announcement;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes an announcement', function () {
    $announcement = Announcement::factory()->create();

    app(DeleteAnnouncementAction::class)->execute($announcement);

    expect(Announcement::find($announcement->id))->toBeNull();
});

test('logs the deletion event', function () {
    $announcement = Announcement::factory()->create();

    app(DeleteAnnouncementAction::class)->execute($announcement);

    expect(Announcement::find($announcement->id))->toBeNull();
});
