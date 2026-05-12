<?php

declare(strict_types=1);

use App\Actions\Schedule\UpdateScheduleAction;
use App\Models\User;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates a schedule entry', function () {
        $user = User::factory()->create();
        $schedule = ScheduleFactory::new()->create(['title' => 'Old Title']);

        app(UpdateScheduleAction::class)->execute($user, $schedule, [
            'title' => 'Updated Title',
        ]);

        expect($schedule->fresh()->title)->toBe('Updated Title');
    });
});
