<?php

declare(strict_types=1);

use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;

beforeEach(function () {
    $user = User::factory()->create();
    $this->user = $user;
});

describe('PruneNotificationsCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('notifications:prune');
    });

    it('fails with days less than 1', function () {
        $this->artisan('notifications:prune', ['--days' => 0])
            ->assertExitCode(1);
    });

    it('deletes old read notifications', function () {
        Notification::factory()->read()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(60),
        ]);

        $this->artisan('notifications:prune', ['--days' => 30])
            ->assertExitCode(0)
            ->expectsOutputToContain('Pruned 1');
    });

    it('does not delete recent read notifications', function () {
        Notification::factory()->read()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10),
        ]);

        $this->artisan('notifications:prune', ['--days' => 30])
            ->assertExitCode(0)
            ->expectsOutputToContain('Pruned 0');
    });

    it('does not delete unread notifications', function () {
        Notification::factory()->unread()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(60),
        ]);

        $this->artisan('notifications:prune', ['--days' => 30])
            ->assertExitCode(0)
            ->expectsOutputToContain('Pruned 0');
    });
});
