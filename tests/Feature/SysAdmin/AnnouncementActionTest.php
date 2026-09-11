<?php

declare(strict_types=1);

use App\Modules\SysAdmin\Domain\Announcement\Actions\PublishAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Actions\SendAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('3S55V: announcement actions', function (): void {
    test('3S55V-FR-ANN-008: send creates a draft stamped with the acting user', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $result = app(SendAnnouncementAction::class)->execute([
            'title' => 'Draft stamped announcement',
            'message' => 'Body of the draft announcement.',
            'type' => 'info',
            'status' => 'draft',
        ]);

        expect($result)->toBeInstanceOf(Announcement::class)
            ->and($result->status)->toBe(AnnouncementStatus::DRAFT);

        $this->assertModelExists($result);
        $this->assertDatabaseHas('announcements', [
            'id' => $result->id,
            'title' => 'Draft stamped announcement',
            'status' => AnnouncementStatus::DRAFT->value,
            'created_by' => $admin->id,
        ]);
    });

    test('3S55V-FR-ANN-007: send rejects a payload without a title', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        expect(fn () => app(SendAnnouncementAction::class)->execute([
            'message' => 'Title is missing here.',
            'type' => 'info',
        ]))->toThrow(ValidationException::class);

        $this->assertDatabaseMissing('announcements', [
            'message' => 'Title is missing here.',
        ]);
    });

    test('3S55V-FR-ANN-009: send with published status persists a published row', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $result = app(SendAnnouncementAction::class)->execute([
            'title' => 'Published on creation',
            'message' => 'Visible immediately.',
            'type' => 'success',
            'status' => 'published',
        ]);

        expect($result->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);
        $this->assertDatabaseHas('announcements', [
            'id' => $result->id,
            'status' => AnnouncementStatus::PUBLISHED->value,
        ]);
    });

    test('3S55V-FR-ANN-010: publish flips a scheduled row to published and clears the schedule', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $announcement = Announcement::factory()->create([
            'status' => AnnouncementStatus::SCHEDULED->value,
            'scheduled_at' => now()->addDay(),
            'created_by' => $admin->id,
        ]);

        app(PublishAnnouncementAction::class)->execute($announcement);

        $fresh = $announcement->fresh();
        expect($fresh->status)->toBe(AnnouncementStatus::PUBLISHED)
            ->and($fresh->scheduled_at)->toBeNull();

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'status' => AnnouncementStatus::PUBLISHED->value,
        ]);
    });
});
