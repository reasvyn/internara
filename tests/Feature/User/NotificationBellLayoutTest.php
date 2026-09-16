<?php

declare(strict_types=1);

use App\Modules\User\Domain\Notify\Models\Notification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('TXR2H: shared-layout bell presence', function (): void {
    test('TXR2H-NFR-NOTIF-007: bell renders on every authenticated page via the shared layout', function (): void {
        $user = User::factory()->create();
        Notification::factory()->unread()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $bellHref = route('notifications');
        $bellLabel = __('notifications.ui.title');

        // Two distinct authenticated pages share the ui::layouts.app shell,
        // so both must carry the header bell link with its accessible name.
        $this->get(route('notifications'))
            ->assertOk()
            ->assertSee('href="'.$bellHref.'"', false);

        $this->get(route('profile'))
            ->assertOk()
            ->assertSee('href="'.$bellHref.'"', false)
            ->assertSee($bellLabel);

        // Guests use the same shell without an authenticated session, so no
        // per-user bell link may leak into the public page.
        auth()->logout();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="'.$bellHref.'"', false);
    });
});
