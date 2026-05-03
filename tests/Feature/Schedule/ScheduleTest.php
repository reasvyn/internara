<?php

declare(strict_types=1);

use App\Domain\Schedule\Models\Schedule;
use App\Domain\User\Models\User;
use App\Enums\Auth\Role as RoleEnum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can view schedule index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.schedules.index'));

    $response->assertOk();
});

test('admin can create a new schedule', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
        'title' => 'Internship Orientation',
        'start_at' => now()->addWeek(),
        'type' => 'orientation',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('schedules', ['title' => 'Internship Orientation']);
});

test('admin can update a schedule', function () {
    $schedule = Schedule::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->put(route('admin.schedules.update', $schedule), [
        'title' => 'Updated Title',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'title' => 'Updated Title']);
});

test('admin can delete a schedule', function () {
    $schedule = Schedule::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->delete(route('admin.schedules.destroy', $schedule));

    $response->assertRedirect();
    $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
});
