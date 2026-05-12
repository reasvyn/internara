<?php

declare(strict_types=1);

use App\Actions\Schedule\CreateScheduleAction;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a schedule entry', function () {
        $user = User::factory()->create();

        $schedule = app(CreateScheduleAction::class)->execute($user, [
            'title' => 'Orientation Day',
            'start_at' => now()->addDay()->format('Y-m-d H:i'),
            'end_at' => now()->addDay()->addHours(2)->format('Y-m-d H:i'),
            'type' => 'orientation',
        ]);

        expect($schedule)->toBeInstanceOf(Schedule::class)
            ->and($schedule->title)->toBe('Orientation Day')
            ->and($schedule->created_by)->toBe($user->id);
    });
});
