<?php

declare(strict_types=1);

use App\Academics\Department\Actions\CreateDepartmentAction;
use App\Academics\Department\Actions\DeleteDepartmentAction;
use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use App\Academics\Department\Models\Department;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('department created event is dispatched via action', function () {
    Event::fake([DepartmentCreated::class]);

    app(CreateDepartmentAction::class)->execute([
        'name' => 'RPL',
    ]);

    Event::assertDispatched(DepartmentCreated::class);
});

test('department deleted event is dispatched via action', function () {
    $department = Department::factory()->create();

    Event::fake([DepartmentDeleted::class]);

    app(DeleteDepartmentAction::class)->execute($department);

    Event::assertDispatched(DepartmentDeleted::class);
});
