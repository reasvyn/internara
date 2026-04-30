<?php

declare(strict_types=1);

use App\Models\Handbook;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can view handbook index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.handbooks.index'));

    $response->assertOk();
})->todo('Implement handbook index route and view');

test('admin can create a new handbook', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.handbooks.store'), [
            'title' => 'Student Handbook 2026',
            'content' => 'This is the handbook content.',
            'version' => '1.0',
            'is_active' => true,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('handbooks', ['title' => 'Student Handbook 2026']);
})->todo('Implement handbook creation');

test('user can acknowledge a handbook', function () {
    $handbook = Handbook::factory()->create(['is_active' => true]);

    $response = $this->actingAs($this->admin)
        ->post(route('handbooks.acknowledge', $handbook));

    $response->assertRedirect();
    $this->assertDatabaseHas('handbook_acknowledgements', [
        'handbook_id' => $handbook->id,
        'user_id' => $this->admin->id,
    ]);
})->todo('Implement handbook acknowledgement');

test('student can view published handbooks', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $response = $this->actingAs($student)
        ->get(route('admin.handbooks.index'));

    $response->assertOk();
})->todo('Implement student handbook view access');
