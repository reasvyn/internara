<?php

declare(strict_types=1);

use App\Domain\Guidance\Models\Handbook;
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

test('admin can view handbook index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.handbooks.index'));

    $response->assertOk();
});

test('admin can create a new handbook', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.handbooks.store'), [
        'title' => 'Student Handbook 2026',
        'content' => 'This is the handbook content.',
        'version' => '1.0',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('handbooks', ['title' => 'Student Handbook 2026']);
});

test('user can acknowledge a handbook', function () {
    $handbook = Handbook::factory()->published()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.handbooks.acknowledge', $handbook),
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('handbook_acknowledgements', [
        'handbook_id' => $handbook->id,
        'user_id' => $this->admin->id,
    ]);
});

test('student cannot access admin handbook management', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $response = $this->actingAs($student)->get(route('admin.handbooks.index'));

    $response->assertForbidden();
});
