<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Actions\MarkAsReadAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(MarkAsReadAction::class);
});

test('marks notification as read', function () {
    $notification = Notification::factory()->create([
        'user_id' => $this->user->id,
        'is_read' => false,
    ]);

    $result = $this->action->execute($notification);

    expect($result->is_read)->toBeTrue();
    expect($result->read_at)->not->toBeNull();
});

test('does not change already read notification', function () {
    $notification = Notification::factory()->create([
        'user_id' => $this->user->id,
        'is_read' => true,
        'read_at' => now()->subHour(),
    ]);

    $result = $this->action->execute($notification);

    expect($result->is_read)->toBeTrue();
    expect($result->read_at->diffInHours(now()))->toBeGreaterThan(0);
});
