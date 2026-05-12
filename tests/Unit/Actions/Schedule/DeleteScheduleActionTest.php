<?php

declare(strict_types=1);

use App\Actions\Schedule\DeleteScheduleAction;
use App\Models\User;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes a schedule entry', function () {
        $user = User::factory()->create();
        $schedule = ScheduleFactory::new()->create();

        app(DeleteScheduleAction::class)->execute($user, $schedule);

        expect($schedule->fresh())->toBeNull();
    });
});
