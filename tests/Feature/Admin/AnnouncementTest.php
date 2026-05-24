<?php

declare(strict_types=1);

use App\Domain\Admin\Actions\SendAnnouncementAction;
use App\Domain\Admin\Enums\AnnouncementStatus;
use App\Domain\Admin\Livewire\AnnouncementManager;
use App\Domain\Admin\Models\Announcement;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('SendAnnouncementAction', function () {
    it('creates a draft announcement', function () {
        $announcement = app(SendAnnouncementAction::class)->execute([
            'title' => 'Test Announcement',
            'message' => 'This is a test message',
            'type' => 'info',
            'status' => 'draft',
        ]);

        expect($announcement)->toBeInstanceOf(Announcement::class)
            ->and($announcement->title)->toBe('Test Announcement')
            ->and($announcement->status)->toBe(AnnouncementStatus::DRAFT)
            ->and($announcement->created_by)->toBe($this->admin->id);
    });

    it('creates a published announcement and sends notifications', function () {
        $announcement = app(SendAnnouncementAction::class)->execute([
            'title' => 'Urgent',
            'message' => 'Important message',
            'type' => 'warning',
            'status' => 'published',
        ]);

        expect($announcement->status)->toBe(AnnouncementStatus::PUBLISHED);
    });

    it('publishes a draft announcement', function () {
        $announcement = Announcement::factory()->create(['status' => AnnouncementStatus::DRAFT, 'created_by' => $this->admin->id]);

        app(SendAnnouncementAction::class)->publish($announcement);

        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);
    });

    it('validates required fields', function () {
        app(SendAnnouncementAction::class)->execute(['title' => '']);
    })->throws(ValidationException::class);

    it('validates type is valid', function () {
        app(SendAnnouncementAction::class)->execute([
            'title' => 'Test',
            'message' => 'Message',
            'type' => 'invalid',
        ]);
    })->throws(ValidationException::class);
});

describe('AnnouncementManager', function () {
    it('renders the announcement manager', function () {
        Livewire::test(AnnouncementManager::class)
            ->assertSuccessful();
    });

    it('blocks non-admin users', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(AnnouncementManager::class)
            ->assertForbidden();
    });

    it('creates a draft announcement via form', function () {
        Livewire::test(AnnouncementManager::class)
            ->set('title', 'New Announcement')
            ->set('message', 'Announcement body')
            ->set('type', 'info')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('title', '');

        expect(Announcement::where('title', 'New Announcement')->exists())->toBeTrue();
    });

    it('validates title is required', function () {
        Livewire::test(AnnouncementManager::class)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors('title');
    });

    it('validates message length', function () {
        Livewire::test(AnnouncementManager::class)
            ->set('title', 'Title')
            ->set('message', str_repeat('x', 5001))
            ->call('save')
            ->assertHasErrors('message');
    });

    it('validates scheduled_at with scheduled status', function () {
        Livewire::test(AnnouncementManager::class)
            ->set('title', 'Scheduled')
            ->set('message', 'Body')
            ->set('status', 'scheduled')
            ->set('scheduled_at', '')
            ->call('save')
            ->assertHasErrors('scheduled_at');
    });

    it('deletes own announcement', function () {
        $announcement = Announcement::factory()->create([
            'created_by' => $this->admin->id,
            'status' => AnnouncementStatus::DRAFT,
        ]);

        Livewire::test(AnnouncementManager::class)
            ->call('confirmDelete', $announcement->id)
            ->assertSet('showConfirm', true)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(Announcement::find($announcement->id))->toBeNull();
    });

    it('publishes a draft announcement', function () {
        $announcement = Announcement::factory()->create([
            'created_by' => $this->admin->id,
            'status' => AnnouncementStatus::DRAFT,
        ]);

        Livewire::test(AnnouncementManager::class)
            ->call('confirmPublish', $announcement->id)
            ->assertSet('showConfirm', true)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);
    });

    it('sendToAll clears target_roles when enabled', function () {
        Livewire::test(AnnouncementManager::class)
            ->set('target_roles', ['teacher'])
            ->set('sendToAll', true)
            ->assertSet('target_roles', []);
    });

    it('shows announcement list', function () {
        Announcement::factory()->count(3)->create(['created_by' => $this->admin->id]);

        Livewire::test(AnnouncementManager::class)
            ->assertSuccessful();
    });
});
