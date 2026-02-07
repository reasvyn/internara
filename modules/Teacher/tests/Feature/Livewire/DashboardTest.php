<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Teacher\Livewire\Dashboard;
use Modules\User\Models\User;

test('teacher dashboard renders correctly', function () {
    $teacher = User::factory()->create()->assignRole('teacher');

    Livewire::actingAs($teacher)
        ->test(Dashboard::class)
        ->assertSee(__('teacher::ui.dashboard.title'));
});
