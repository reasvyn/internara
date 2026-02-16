<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Teacher\Livewire\Dashboard;
use Modules\User\Models\User;

beforeEach(function () {
    \Modules\Permission\Models\Role::create(['name' => 'teacher', 'guard_name' => 'web']);
});

test('teacher dashboard renders correctly', function () {
    $teacher = User::factory()->create()->assignRole('teacher');

    Livewire::actingAs($teacher)
        ->test(Dashboard::class)
        ->assertSee(__('teacher::ui.dashboard.title'));
});
