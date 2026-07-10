<?php

declare(strict_types=1);

use App\User\Dashboard\Livewire\SupervisorDashboard;
use Livewire\Component;

test('extends livewire component', function () {
    $reflection = new ReflectionClass(SupervisorDashboard::class);
    expect($reflection->isSubclassOf(Component::class))->toBeTrue();
});
