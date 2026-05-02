<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Log\Livewire\ActivityFeed;
use Modules\Log\Models\Activity;
use Modules\User\Models\User;

test('it renders activity feed with system entries', function () {
    Activity::create([
        'log_name' => 'system',
        'description' => 'System started',
    ]);

    Livewire::test(ActivityFeed::class)
        ->assertSee(__('log::ui.activity_feed'))
        ->assertSee(__('log::ui.system'))
        ->assertSee('System started');
});

test('it renders activity feed with user entries', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    activity()->causedBy($user)->log('User logged in');

    Livewire::test(ActivityFeed::class)->assertSee('Jane Doe')->assertSee('User logged in');
});
