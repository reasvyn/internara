<?php

declare(strict_types=1);

use App\Domain\User\Livewire\NotificationBell;
use App\Domain\User\Livewire\NotificationCenter;
use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;

beforeEach(function () {
    app()->setLocale('en');
});

describe('NotificationCenter', function () {
    it('renders empty notification list', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(NotificationCenter::class)
            ->assertSuccessful();
    });

    it('shows user notifications', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Notification::factory()->for($user)->create([
            'title' => 'Test Notification',
            'message' => 'Test body',
        ]);

        Livewire::test(NotificationCenter::class)
            ->assertSee('Test Notification');
    });

    it('marks single notification as read', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $notification = Notification::factory()->for($user)->create(['is_read' => false]);

        Livewire::test(NotificationCenter::class)
            ->call('markAsRead', $notification->id);

        expect($notification->fresh()->is_read)->toBeTrue();
    });

    it('marks all notifications as read', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Notification::factory()->for($user)->count(3)->create(['is_read' => false]);

        Livewire::test(NotificationCenter::class)
            ->call('markAllAsRead');

        expect(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
    });

    it('marks selected notifications as read', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $n1 = Notification::factory()->for($user)->create(['is_read' => false]);
        $n2 = Notification::factory()->for($user)->create(['is_read' => false]);
        $n3 = Notification::factory()->for($user)->create(['is_read' => false]);

        Livewire::test(NotificationCenter::class)
            ->set('selectedIds', [$n1->id, $n2->id])
            ->call('markSelectedAsRead');

        expect($n1->fresh()->is_read)->toBeTrue()
            ->and($n2->fresh()->is_read)->toBeTrue()
            ->and($n3->fresh()->is_read)->toBeFalse();
    });

    it('deletes selected notifications', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $n1 = Notification::factory()->for($user)->create();
        $n2 = Notification::factory()->for($user)->create();

        Livewire::test(NotificationCenter::class)
            ->set('selectedIds', [$n1->id, $n2->id])
            ->call('deleteSelected');

        expect(Notification::find($n1->id))->toBeNull()
            ->and(Notification::find($n2->id))->toBeNull();
    });

    it('filters notifications by search', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Notification::factory()->for($user)->create(['title' => 'Annual Report']);
        Notification::factory()->for($user)->create(['title' => 'Meeting Reminder']);

        Livewire::test(NotificationCenter::class)
            ->set('search', 'Meeting')
            ->assertSee('Meeting Reminder')
            ->assertDontSee('Annual Report');
    });

    it('shows only user-specific notifications', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->actingAs($user1);

        Notification::factory()->for($user1)->create(['title' => 'User1 Notif']);
        Notification::factory()->for($user2)->create(['title' => 'User2 Notif']);

        Livewire::test(NotificationCenter::class)
            ->assertSee('User1 Notif')
            ->assertDontSee('User2 Notif');
    });
});

describe('NotificationBell', function () {
    it('shows unread count', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Notification::factory()->for($user)->count(3)->create(['is_read' => false]);

        Livewire::test(NotificationBell::class)
            ->assertSet('unreadCount', 3);
    });

    it('shows zero when no unread', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(NotificationBell::class)
            ->assertSet('unreadCount', 0);
    });

    it('updates unread count via event listener', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(NotificationBell::class);
        $component->assertSet('unreadCount', 0);

        Notification::factory()->for($user)->create(['is_read' => false]);

        $component->dispatch('notification-read')
            ->assertSet('unreadCount', 1);
    });
});
