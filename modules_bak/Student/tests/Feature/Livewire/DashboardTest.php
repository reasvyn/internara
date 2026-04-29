<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Permission\Models\Role;
use Modules\Student\Livewire\Dashboard;
use Modules\User\Models\User;

beforeEach(function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

test('student dashboard renders correctly', function () {
    $student = User::factory()->create()->assignRole('student');

    Livewire::actingAs($student)
        ->test(Dashboard::class)
        ->assertSee(__('student::ui.dashboard.title'));
});
