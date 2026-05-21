<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Schedule\Actions\CreateScheduleAction;
use App\Domain\Schedule\Actions\DeleteScheduleAction;
use App\Domain\Schedule\Actions\UpdateScheduleAction;
use App\Domain\Schedule\Models\Schedule;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
});

describe('CreateScheduleAction', function () {
    it('creates a schedule entry', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN->value);

        $schedule = app(CreateScheduleAction::class)->execute($user, [
            'title' => 'Orientation Day',
            'description' => 'Welcome event',
            'type' => 'orientation',
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(5)->addHours(3),
        ]);

        expect($schedule)->toBeInstanceOf(Schedule::class)
            ->and($schedule->title)->toBe('Orientation Day')
            ->and($schedule->created_by)->toBe($user->id);
    });
});

describe('UpdateScheduleAction', function () {
    it('updates a schedule entry', function () {
        $user = User::factory()->create();
        $schedule = Schedule::factory()->create();

        $updated = app(UpdateScheduleAction::class)->execute($user, $schedule, [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

        expect($updated->title)->toBe('Updated Title')
            ->and($updated->description)->toBe('Updated description');
    });
});

describe('DeleteScheduleAction', function () {
    it('deletes a schedule entry', function () {
        $user = User::factory()->create();
        $schedule = Schedule::factory()->create();

        app(DeleteScheduleAction::class)->execute($user, $schedule);

        expect(Schedule::find($schedule->id))->toBeNull();
    });
});
