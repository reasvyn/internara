<?php

declare(strict_types=1);

use App\User\Dashboard\Livewire\StudentDashboard;
use Livewire\Component;

test('extends livewire component', function () {
    $reflection = new ReflectionClass(StudentDashboard::class);
    expect($reflection->isSubclassOf(Component::class))->toBeTrue();
});
