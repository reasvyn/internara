<?php

declare(strict_types=1);

use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('prunes read notifications older than default 30 days', function () {
    Notification::factory()->create([
        'is_read' => true,
        'created_at' => now()->subDays(31),
    ]);
    Notification::factory()->create([
        'is_read' => true,
        'created_at' => now()->subDays(29),
    ]);

    $this->artisan('notifications:prune')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.prune_notifications.completed', ['count' => 1, 'days' => 30]));
});

test('prunes with custom retention days', function () {
    Notification::factory()->create([
        'is_read' => true,
        'created_at' => now()->subDays(8),
    ]);
    Notification::factory()->create([
        'is_read' => true,
        'created_at' => now()->subDays(6),
    ]);

    $this->artisan('notifications:prune', ['--days' => 7])
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.prune_notifications.completed', ['count' => 1, 'days' => 7]));
});

test('does not delete unread notifications', function () {
    Notification::factory()->create([
        'is_read' => false,
        'created_at' => now()->subDays(60),
    ]);

    $this->artisan('notifications:prune')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.prune_notifications.completed', ['count' => 0, 'days' => 30]));
});

test('fails when days option is less than 1', function () {
    $this->artisan('notifications:prune', ['--days' => 0])
        ->assertExitCode(1)
        ->expectsOutputToContain(__('sysadmin.prune_notifications.invalid_days'));
});
