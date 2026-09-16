<?php

declare(strict_types=1);

use App\Modules\User\Domain\Notify\Livewire\NotificationBell;
use App\Modules\User\Domain\Notify\Livewire\NotificationCenter;
use App\Modules\User\Domain\Notify\Models\Notification;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('TXR2H: notification center and bell', function (): void {
    test('TXR2H-FR-NOTIF-010: center lists only rows owned by the viewer; TXR2H-NFR-NOTIF-002: cross-user rows stay unreachable at the query layer', function (): void {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        Notification::factory()->create([
            'user_id' => $viewer->id, 'title' => 'Penempatan Milik Saya',
        ]);
        Notification::factory()->create([
            'user_id' => $other->id, 'title' => 'Rahasia Orang Lain',
        ]);
        $this->actingAs($viewer);

        Livewire::test(NotificationCenter::class)
            ->assertSee('Penempatan Milik Saya')
            ->assertDontSee('Rahasia Orang Lain');

        // Policy layer for the same boundary is pinned by NotificationPolicyTest (FR-NOTIF-020).
        expect(Notification::where('user_id', $viewer->id)->count())->toBe(1);
    });

    test('TXR2H-FR-NOTIF-011: search matches title and message while the status filter splits read from unread', function (): void {
        $user = User::factory()->create();
        Notification::factory()->unread()->create([
            'user_id' => $user->id, 'title' => 'Penempatan Acme Corp', 'message' => 'Jadwal kunjungan.',
        ]);
        Notification::factory()->read()->create([
            'user_id' => $user->id, 'title' => 'Pengumuman Libur', 'message' => 'Libur nasional.',
        ]);
        Notification::factory()->unread()->create([
            'user_id' => $user->id, 'title' => 'Info Umum', 'message' => 'kode-unik-msg-xyz wajib dibaca.',
        ]);
        $this->actingAs($user);

        Livewire::test(NotificationCenter::class)
            ->set('search', 'Acme')
            ->assertSee('Penempatan Acme Corp')
            ->assertDontSee('Pengumuman Libur');

        Livewire::test(NotificationCenter::class)
            ->set('search', 'kode-unik-msg-xyz')
            ->assertSee('Info Umum')
            ->assertDontSee('Penempatan Acme Corp');

        Livewire::test(NotificationCenter::class)
            ->set('filters.status', 'unread')
            ->assertSee('Penempatan Acme Corp')
            ->assertDontSee('Pengumuman Libur');

        Livewire::test(NotificationCenter::class)
            ->set('filters.status', 'read')
            ->assertSee('Pengumuman Libur')
            ->assertDontSee('Penempatan Acme Corp');
    });

    test('TXR2H-FR-NOTIF-012: opening a row marks it read, notifies the bell, and shows the viewer', function (): void {
        $user = User::factory()->create();
        $row = Notification::factory()->unread()->create([
            'user_id' => $user->id, 'title' => 'Undangan Verifikasi', 'link' => '/student/dashboard',
        ]);
        $this->actingAs($user);

        Livewire::test(NotificationCenter::class)
            ->call('viewNotification', $row->id)
            ->assertDispatched('notification-read')
            ->assertSet('showViewer', true)
            ->assertSet('viewingNotificationId', $row->id);

        expect($row->fresh()->is_read)->toBeTrue();
        expect($row->fresh()->read_at)->not->toBeNull();
    });

    test('TXR2H-FR-NOTIF-012: opening another user row fails closed without changing state', function (): void {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Notification::factory()->unread()->create(['user_id' => $other->id]);
        $this->actingAs($viewer);

        expect(fn () => Livewire::test(NotificationCenter::class)->call('viewNotification', $foreign->id))
            ->toThrow(ModelNotFoundException::class);

        expect($foreign->fresh()->is_read)->toBeFalse();
    });

    test('TXR2H-FR-NOTIF-013: mark paths delegate to their actions and refresh the bell', function (): void {
        $user = User::factory()->create();
        $first = Notification::factory()->unread()->create(['user_id' => $user->id]);
        $second = Notification::factory()->unread()->create(['user_id' => $user->id]);
        $third = Notification::factory()->unread()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(NotificationCenter::class)
            ->call('markAsRead', $first->id)
            ->assertDispatched('notification-read');
        expect($first->fresh()->is_read)->toBeTrue();
        expect($second->fresh()->is_read)->toBeFalse();

        Livewire::test(NotificationCenter::class)
            ->set('selectedIds', [$second->id, $third->id])
            ->call('markSelectedAsRead')
            ->assertDispatched('notifications-read')
            ->assertSet('selectedIds', []);
        expect($second->fresh()->is_read)->toBeTrue();
        expect($third->fresh()->is_read)->toBeTrue();

        $fourth = Notification::factory()->unread()->create(['user_id' => $user->id]);
        Livewire::test(NotificationCenter::class)
            ->call('markAllAsRead')
            ->assertDispatched('notifications-read');
        expect($fourth->fresh()->is_read)->toBeTrue();
        expect(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
    });

    test('TXR2H-FR-NOTIF-014: batch delete removes owned rows and skips foreign ids without touching them', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = Notification::factory()->unread()->count(2)->create(['user_id' => $user->id]);
        $foreign = Notification::factory()->unread()->create(['user_id' => $other->id]);
        $this->actingAs($user);

        Livewire::test(NotificationCenter::class)
            ->set('selectedIds', [$mine[0]->id, $mine[1]->id, $foreign->id])
            ->call('confirmAction')
            ->assertSet('selectedIds', []);

        expect(Notification::where('id', $mine[0]->id)->exists())->toBeFalse();
        expect(Notification::where('id', $mine[1]->id)->exists())->toBeFalse();
        expect(Notification::where('id', $foreign->id)->exists())->toBeTrue('foreign row must survive a crafted selection');

        // NOTE: the RejectedException-to-toast branch has no trigger in
        // DeleteNotificationAction (no business-rule refusal path), so only the
        // ownership-safe bulk path is asserted here.
    });

    test('TXR2H-FR-NOTIF-015: bell caches the unread count on the registered key and refetches after the TTL', function (): void {
        $user = User::factory()->create();
        Notification::factory()->unread()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $key = config('cache-keys.notification_unread').$user->id;

        Livewire::test(NotificationBell::class)->assertSet('unreadCount', 1);
        expect(Cache::has($key))->toBeTrue();
        expect((int) Cache::get($key))->toBe(1);

        // A second row lands while the entry is warm: the bell still serves the cache.
        Notification::factory()->unread()->create(['user_id' => $user->id]);
        Livewire::test(NotificationBell::class)->assertSet('unreadCount', 1);

        // Past the 60-second TTL the count is recomputed instead of served stale.
        $this->travel(61)->seconds();
        Livewire::test(NotificationBell::class)->assertSet('unreadCount', 2);
        $this->travelBack();
    });

    test('TXR2H-FR-NOTIF-016: bell mounts once, stays zero for guests, and refreshes on read events', function (): void {
        $listeners = (new NotificationBell)->getListeners();
        expect($listeners['notification-read'] ?? null)->toBe('updateUnreadCount');
        expect($listeners['notifications-read'] ?? null)->toBe('updateUnreadCount');

        Livewire::test(NotificationBell::class)->assertSet('unreadCount', 0);

        $user = User::factory()->create();
        Notification::factory()->unread()->count(2)->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(NotificationBell::class)
            ->assertSet('unreadCount', 2)
            ->set('unreadCount', 0)
            ->dispatch('notification-read')
            ->assertSet('unreadCount', 2)
            ->set('unreadCount', 0)
            ->dispatch('notifications-read')
            ->assertSet('unreadCount', 2);
    });

    test('TXR2H-NFR-NOTIF-010: empty histories and guest sessions render with zero-count defaults', function (): void {
        $freshUser = User::factory()->create();
        $this->actingAs($freshUser);

        Livewire::test(NotificationBell::class)->assertSet('unreadCount', 0);
        Livewire::test(NotificationCenter::class)->assertSee(__('notifications.ui.title'));

        auth()->logout();

        Livewire::test(NotificationBell::class)->assertSet('unreadCount', 0);
    });

    test('TXR2H-UC-NOTIF-001: student searches, filters, opens the placement row, and the bell drops by one', function (): void {
        $student = User::factory()->create();
        Notification::factory()->unread()->create([
            'user_id' => $student->id, 'title' => 'Penempatan PT Maju Jaya', 'message' => 'Pembimbing disetujui.',
        ]);
        Notification::factory()->unread()->count(2)->create(['user_id' => $student->id]);
        $this->actingAs($student);

        $placement = Notification::where('user_id', $student->id)
            ->where('title', 'like', '%Maju Jaya%')->firstOrFail();

        Livewire::test(NotificationCenter::class)
            ->set('search', 'Maju Jaya')
            ->set('filters.status', 'unread')
            ->assertSee('Penempatan PT Maju Jaya')
            ->call('viewNotification', $placement->id)
            ->assertDispatched('notification-read')
            ->assertSet('showViewer', true);

        Livewire::test(NotificationBell::class)->assertSet('unreadCount', 2);
    });

    test('TXR2H-UC-NOTIF-002: admin marks everything read and the bell settles at zero', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Notification::factory()->unread()->count(4)->create(['user_id' => $admin->id]);
        $peer = User::factory()->create();
        Notification::factory()->unread()->count(2)->create(['user_id' => $peer->id]);
        $this->actingAs($admin);

        Livewire::test(NotificationCenter::class)
            ->call('markAllAsRead')
            ->assertDispatched('notifications-read');

        expect(Notification::where('user_id', $admin->id)->where('is_read', false)->count())->toBe(0);
        expect(Notification::where('user_id', $peer->id)->where('is_read', false)->count())->toBe(2);

        Livewire::test(NotificationBell::class)->assertSet('unreadCount', 0);
    });

    test('TXR2H-UC-NOTIF-003: supervisor batch-marks the weekend backlog then deletes the obsolete rows', function (): void {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $backlog = Notification::factory()->unread()->count(5)->create(['user_id' => $supervisor->id]);
        $other = User::factory()->create();
        $foreign = Notification::factory()->unread()->create(['user_id' => $other->id]);
        $this->actingAs($supervisor);

        $selected = $backlog->take(3)->pluck('id')->all();

        Livewire::test(NotificationCenter::class)
            ->set('selectedIds', $selected)
            ->call('markSelectedAsRead')
            ->assertDispatched('notifications-read')
            ->assertSet('selectedIds', []);
        expect(Notification::whereIn('id', $selected)->where('is_read', false)->count())->toBe(0);

        $obsolete = $backlog->skip(3)->take(2)->pluck('id')->all();
        Livewire::test(NotificationCenter::class)
            ->set('selectedIds', [...$obsolete, $foreign->id])
            ->call('confirmAction')
            ->assertSet('selectedIds', []);

        foreach ($obsolete as $id) {
            expect(Notification::where('id', $id)->exists())->toBeFalse();
        }
        expect(Notification::where('id', $foreign->id)->exists())->toBeTrue();
        expect(Notification::where('user_id', $supervisor->id)->count())->toBe(3);
    });
});
