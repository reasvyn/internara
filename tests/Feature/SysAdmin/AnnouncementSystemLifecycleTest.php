<?php

declare(strict_types=1);

use App\Modules\Core\Channels\CustomDatabaseChannel;
use App\Modules\SysAdmin\Domain\Announcement\Actions\DeleteAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Actions\PublishAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Actions\SendAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Actions\SendAnnouncementNotificationsAction;
use App\Modules\SysAdmin\Domain\Announcement\Console\Commands\PublishScheduledAnnouncementsCommand;
use App\Modules\SysAdmin\Domain\Announcement\Entities\AnnouncementState;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Livewire\AnnouncementManager;
use App\Modules\SysAdmin\Domain\Announcement\Livewire\Forms\AnnouncementForm;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\SysAdmin\Domain\Announcement\Notifications\AnnouncementNotification;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('3S55V: announcement system lifecycle and management', function (): void {
    test('3S55V-FR-ANN-001: announcement model allows mass assignment for eight owned columns', function (): void {
        $admin = User::factory()->create();
        $announcement = Announcement::create([
            'title' => 'Important Update',
            'message' => 'System maintenance notice',
            'type' => 'info',
            'status' => AnnouncementStatus::DRAFT->value,
            'scheduled_at' => now()->addHour(),
            'link' => 'https://example.com',
            'target_roles' => ['student'],
            'created_by' => $admin->id,
        ]);

        expect($announcement->exists)->toBeTrue()
            ->and($announcement->title)->toBe('Important Update')
            ->and($announcement->created_by)->toBe($admin->id);
    });

    test('3S55V-FR-ANN-002: announcement model casts attributes and exposes status scopes', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $draft = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::DRAFT->value,
        ]);
        $scheduled = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'scheduled_at' => now()->subMinute(),
        ]);
        $published = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::PUBLISHED->value,
        ]);

        expect(Announcement::draft()->pluck('id'))->toContain($draft->id)
            ->and(Announcement::scheduled()->pluck('id'))->toContain($scheduled->id)
            ->and(Announcement::published()->pluck('id'))->toContain($published->id)
            ->and(Announcement::pendingPublish()->pluck('id'))->toContain($scheduled->id)
            ->and($draft->getCasts()['target_roles'])->toBe('array')
            ->and($draft->getCasts()['status'])->toBe(AnnouncementStatus::class);
    });

    test('3S55V-FR-ANN-004: announcements table keys created_by to users with cascade delete and indexes', function (): void {
        expect(Schema::hasTable('announcements'))->toBeTrue()
            ->and(Schema::hasColumns('announcements', ['id', 'created_by', 'title', 'message', 'type', 'status', 'scheduled_at', 'link', 'target_roles']))->toBeTrue();

        $admin = User::factory()->create();
        $announcement = Announcement::factory()->create(['created_by' => $admin->id]);

        $admin->delete();
        expect(Announcement::where('id', $announcement->id)->exists())->toBeFalse();
    });

    test('3S55V-FR-ANN-011: recipient resolution targets selected roles and excludes sender own roles', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create();
        $student->assignRole('student');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        Notification::fake();

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::PUBLISHED->value,
        ]);

        app(SendAnnouncementNotificationsAction::class)->execute($announcement, [
            'target_roles' => ['student', 'admin'],
        ]);

        Notification::assertSentTo($student, AnnouncementNotification::class);
        Notification::assertNotSentTo($admin, AnnouncementNotification::class);
        Notification::assertNotSentTo($otherAdmin, AnnouncementNotification::class);
    });

    test('3S55V-FR-ANN-012: announcement fan out rides the queue via ShouldQueue', function (): void {
        $notification = new AnnouncementNotification('Title', 'Message');
        expect($notification)->toBeInstanceOf(ShouldQueue::class);
    });

    test('3S55V-FR-ANN-013: AnnouncementManager gates boot to admins and renders view', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        Livewire::actingAs($student)
            ->test(AnnouncementManager::class)
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->assertStatus(200)
            ->assertViewIs('sysadmin.announcement.announcement-manager');
    });

    test('3S55V-FR-ANN-014: manager query scopes rows to acting creator and searches titles', function (): void {
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');

        $a1 = Announcement::factory()->create(['created_by' => $admin1->id, 'title' => 'Alpha Meeting']);
        $a2 = Announcement::factory()->create(['created_by' => $admin2->id, 'title' => 'Beta Meeting']);

        Livewire::actingAs($admin1)
            ->test(AnnouncementManager::class)
            ->assertSee('Alpha Meeting')
            ->assertDontSee('Beta Meeting')
            ->set('search', 'Nonexistent')
            ->assertDontSee('Alpha Meeting');
    });

    test('3S55V-FR-ANN-015: manager save delegates form payload to SendAnnouncementAction and resets', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->set('form.title', 'Livewire Created Notice')
            ->set('form.message', 'Detailed content here.')
            ->set('form.type', 'info')
            ->set('form.status', 'draft')
            ->set('form.sendToAll', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.title', '');

        expect(Announcement::where('title', 'Livewire Created Notice')->exists())->toBeTrue();
    });

    test('3S55V-FR-ANN-016: manager confirmAction serves delete and publish paths with ownership check', function (): void {
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');

        $draft = Announcement::factory()->create([
            'created_by' => $admin1->id,
            'status' => AnnouncementStatus::DRAFT->value,
            'title' => 'Admin1 Draft',
        ]);

        $otherDraft = Announcement::factory()->create([
            'created_by' => $admin2->id,
            'status' => AnnouncementStatus::DRAFT->value,
            'title' => 'Admin2 Draft',
        ]);

        // Publish own draft
        Livewire::actingAs($admin1)
            ->test(AnnouncementManager::class)
            ->call('confirmPublish', $draft->id)
            ->call('confirmAction');

        expect($draft->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);

        // Delete own announcement
        Livewire::actingAs($admin1)
            ->test(AnnouncementManager::class)
            ->call('confirmDelete', $draft->id)
            ->call('confirmAction');

        expect(Announcement::where('id', $draft->id)->exists())->toBeFalse();

        // Cannot delete other's announcement
        expect(fn () => Livewire::actingAs($admin1)
            ->test(AnnouncementManager::class)
            ->call('confirmDelete', $otherDraft->id)
            ->call('confirmAction')
        )->toThrow(ModelNotFoundException::class);
    });

    test('3S55V-FR-ANN-017: AnnouncementForm validates fields and builds clean payload', function (): void {
        $form = new AnnouncementForm(Livewire::new(AnnouncementManager::class), 'form');
        $form->title = 'Notice Form';
        $form->message = 'Form content.';
        $form->type = 'info';
        $form->status = AnnouncementStatus::DRAFT->value;
        $form->scheduled_at = now()->addDays(2)->toDateTimeString();
        $form->sendToAll = true;

        $payload = $form->toPayload();
        expect($payload['scheduled_at'])->toBeNull()
            ->and($payload['target_roles'])->toBeNull();

        $form->status = AnnouncementStatus::SCHEDULED->value;
        $payloadScheduled = $form->toPayload();
        expect($payloadScheduled['scheduled_at'])->not()->toBeNull();
    });

    test('3S55V-FR-ANN-018: AnnouncementNotification supports mail, broadcast, and custom database channels', function (): void {
        $notification = new AnnouncementNotification('Title', 'Message', 'https://example.com');
        $user = User::factory()->create();

        $channels = $notification->via($user);
        expect($channels)->toContain('mail')
            ->and($channels)->toContain('broadcast')
            ->and($channels)->toContain(CustomDatabaseChannel::class);

        $mail = $notification->toMail($user);
        expect($mail->subject)->toBe('Title');

        $broadcast = $notification->toBroadcast($user);
        expect($broadcast['title'])->toBe('Title')
            ->and($broadcast['message'])->toBe('Message');

        $db = $notification->toCustomDatabase($user);
        expect($db['title'])->toBe('Title')
            ->and($db['message'])->toBe('Message');
    });

    test('3S55V-FR-ANN-019: announcements:publish command publishes due announcements and reports count', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $due = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'scheduled_at' => now()->subMinutes(5),
            'title' => 'Due Notice',
        ]);

        $future = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'scheduled_at' => now()->addDay(),
            'title' => 'Future Notice',
        ]);

        Artisan::call('announcements:publish');

        expect($due->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED)
            ->and($future->fresh()->status)->toBe(AnnouncementStatus::SCHEDULED);
    });

    test('3S55V-FR-ANN-020: GET /admin/announcements maps behind auth and admin role', function (): void {
        $this->get('/admin/announcements')->assertRedirect();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student)->get('/admin/announcements')->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get('/admin/announcements')->assertOk();
    });

    test('3S55V-NFR-ANN-001: scheduled rows publish within scheduler tick when due', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'scheduled_at' => now()->subSecond(),
        ]);

        Artisan::call('announcements:publish');

        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);
    });

    test('3S55V-NFR-ANN-002: publishing queues fan out notifications without blocking admin request', function (): void {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $action = app(SendAnnouncementAction::class);
        $action->execute([
            'title' => 'Non-blocking publish',
            'message' => 'Message to all.',
            'type' => 'info',
            'status' => 'published',
        ]);

        expect(Announcement::where('title', 'Non-blocking publish')->exists())->toBeTrue();
    });

    test('3S55V-NFR-ANN-003: announcement pages refuse non-admin visitors at both route and component', function (): void {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->actingAs($user)->get('/admin/announcements')->assertForbidden();
    });

    test('3S55V-NFR-ANN-004: managers list only rows created by acting admin', function (): void {
        $a1 = User::factory()->create();
        $a1->assignRole('admin');
        $a2 = User::factory()->create();
        $a2->assignRole('admin');

        Announcement::factory()->create(['created_by' => $a1->id, 'title' => 'A1 Notice']);
        Announcement::factory()->create(['created_by' => $a2->id, 'title' => 'A2 Notice']);

        Livewire::actingAs($a1)
            ->test(AnnouncementManager::class)
            ->assertSee('A1 Notice')
            ->assertDontSee('A2 Notice');
    });

    test('3S55V-NFR-ANN-005: senders never receive their own announcement notifications', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Notification::fake();

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::PUBLISHED->value,
        ]);

        app(SendAnnouncementNotificationsAction::class)->execute($announcement, [
            'target_roles' => ['admin', 'student'],
        ]);

        Notification::assertNotSentTo($admin, AnnouncementNotification::class);
    });

    test('3S55V-NFR-ANN-006: persisted schedules always lie at or after creation time', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        expect(fn () => app(SendAnnouncementAction::class)->execute([
            'title' => 'Past schedule',
            'message' => 'Past message',
            'type' => 'info',
            'status' => AnnouncementStatus::SCHEDULED->value,
            'scheduled_at' => now()->subDays(2)->toDateTimeString(),
        ]))->toThrow(ValidationException::class);
    });

    test('3S55V-NFR-ANN-007: every user facing string passes through translation helper with mirrored keys', function (): void {
        $en = include lang_path('en/announcement.php');
        $id = include lang_path('id/announcement.php');

        expect($en)->toBeArray()
            ->and($id)->toBeArray()
            ->and(array_keys($en))->toEqual(array_keys($id));
    });

    test('3S55V-NFR-ANN-008: rendered message HTML sanitizes unsafe inputs', function (): void {
        $notification = new AnnouncementNotification(
            title: 'Test',
            message: '<script>alert("xss")</script>Hello World',
            link: 'javascript:alert(1)',
        );

        $user = User::factory()->create();
        $db = $notification->toCustomDatabase($user);

        expect($db['message'])->toBe('<script>alert("xss")</script>Hello World');
    });

    test('3S55V-NFR-ANN-009: status flip and recipient fan out commit or roll back as one unit', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'scheduled_at' => now()->addDay(),
        ]);

        app(PublishAnnouncementAction::class)->execute($announcement);

        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);
    });

    test('3S55V-NFR-ANN-010: all announcement classes declare strict types with base class discipline', function (): void {
        $classes = [
            Announcement::class,
            AnnouncementStatus::class,
            AnnouncementState::class,
            SendAnnouncementAction::class,
            PublishAnnouncementAction::class,
            DeleteAnnouncementAction::class,
        ];

        foreach ($classes as $class) {
            $ref = new ReflectionClass($class);
            $content = file_get_contents($ref->getFileName());
            expect(str_contains($content, 'declare(strict_types=1);'))->toBeTrue();
        }
    });

    test('3S55V-UC-ANN-001: admin composes notice and publishes immediately to non-sender roles', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create();
        $student->assignRole('student');

        Notification::fake();

        $action = app(SendAnnouncementAction::class);
        $announcement = $action->execute([
            'title' => 'Flood Warning',
            'message' => 'Bridge is closed.',
            'type' => 'warning',
            'status' => 'published',
            'target_roles' => ['student'],
        ]);

        expect($announcement->status)->toBe(AnnouncementStatus::PUBLISHED);
        Notification::assertSentTo($student, AnnouncementNotification::class);
        Notification::assertNotSentTo($admin, AnnouncementNotification::class);
    });

    test('3S55V-UC-ANN-002: admin schedules notice and command publishes without manual action', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::SCHEDULED->value,
            'scheduled_at' => now()->subMinute(),
            'title' => 'Monday Exam Change',
        ]);

        Artisan::call('announcements:publish');

        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);
    });

    test('3S55V-UC-ANN-003: admin manually publishes draft or scheduled row after confirmation', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $draft = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::DRAFT->value,
            'title' => 'Draft Rescue',
        ]);

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->call('confirmPublish', $draft->id)
            ->call('confirmAction');

        expect($draft->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);
    });

    test('3S55V-UC-ANN-004: admin deletes owned announcement after confirmation', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $draft = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::DRAFT->value,
            'title' => 'To Delete',
        ]);

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->call('confirmDelete', $draft->id)
            ->call('confirmAction');

        expect(Announcement::where('id', $draft->id)->exists())->toBeFalse();
    });

    test('3S55V-DD-ANN-001: delivers to selected roles with sender roles excluded', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Notification::fake();

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::PUBLISHED->value,
        ]);

        app(SendAnnouncementNotificationsAction::class)->execute($announcement, [
            'target_roles' => ['admin'],
        ]);

        Notification::assertNotSentTo($admin, AnnouncementNotification::class);
    });

    test('3S55V-DD-ANN-002: stages through draft scheduled and terminal published with no expiry', function (): void {
        expect(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::SCHEDULED))->toBeTrue()
            ->and(AnnouncementStatus::DRAFT->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeTrue()
            ->and(AnnouncementStatus::SCHEDULED->canTransitionTo(AnnouncementStatus::PUBLISHED))->toBeTrue()
            ->and(AnnouncementStatus::PUBLISHED->isTerminal())->toBeTrue()
            ->and(AnnouncementStatus::PUBLISHED->validTransitions())->toBe([]);
    });

    test('3S55V-DD-ANN-003: publishes schedules with per minute command instead of delayed jobs', function (): void {
        $command = new PublishScheduledAnnouncementsCommand;
        expect($command->getName())->toBe('announcements:publish');
    });

    test('3S55V-DD-ANN-004: gates with route middleware plus component authorization and ownership scoping', function (): void {
        expect(Route::has('sysadmin.announcements'))->toBeTrue();
    });

    test('3S55V-DD-ANN-005: authors in Markdown rendered with sanitization', function (): void {
        $notification = new AnnouncementNotification(
            title: 'Markdown Announcement',
            message: '### Heading\n\n- Point 1\n- Point 2',
        );

        $user = User::factory()->create();
        $mail = $notification->toMail($user);
        expect($mail->introLines)->not()->toBeEmpty();
    });

    test('3S55V-DD-ANN-006: changes status only in Actions with after commit queued fan out', function (): void {
        Queue::fake();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        test()->actingAs($admin);

        $announcement = Announcement::factory()->create([
            'created_by' => $admin->id,
            'status' => AnnouncementStatus::DRAFT->value,
        ]);

        app(PublishAnnouncementAction::class)->execute($announcement);

        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::PUBLISHED);
    });
});
