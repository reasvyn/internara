<?php

declare(strict_types=1);

use App\Models\Schedule;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can view schedule index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.schedules.index'));

    $response->assertOk();
})->todo('Implement schedule index route and view');

test('admin can create a new schedule', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.schedules.store'), [
            'title' => 'Internship Orientation',
            'start_at' => now()->addWeek(),
            'type' => 'orientation',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('schedules', ['title' => 'Internship Orientation']);
})->todo('Implement schedule creation');

test('admin can update a schedule', function () {
    $schedule = Schedule::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)
        ->put(route('admin.schedules.update', $schedule), [
            'title' => 'Updated Title',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'title' => 'Updated Title']);
})->todo('Implement schedule update');

test('admin can delete a schedule', function () {
    $schedule = Schedule::factory()->create(['created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)
        ->delete(route('admin.schedules.destroy', $schedule));

    $response->assertRedirect();
    $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
})->todo('Implement schedule deletion');
