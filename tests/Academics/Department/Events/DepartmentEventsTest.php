<?php

declare(strict_types=1);

use App\Academics\Department\Actions\CreateDepartmentAction;
use App\Academics\Department\Actions\DeleteDepartmentAction;
use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use App\Academics\Department\Events\DepartmentUpdated;
use App\Academics\Department\Models\Department;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

function makeDepartment(string $id): Department
{
    $model = new class extends Department {};
    $model->forceFill(['id' => $id]);

    return $model;
}

beforeEach(function () {
    Cache::flush();
});

describe('event construction', function () {
    it('department created event name and payload', function () {
        $event = new DepartmentCreated(makeDepartment('d-1'));

        expect($event->department->id)->toBe('d-1');
        expect($event->eventName())->toBe('department.created');
        expect($event->toPayload())->toHaveKey('department_id');
    });

    it('department updated event name and payload', function () {
        $event = new DepartmentUpdated(makeDepartment('d-2'));

        expect($event->department->id)->toBe('d-2');
        expect($event->eventName())->toBe('department.updated');
        expect($event->toPayload())->toHaveKey('department_id');
    });

    it('department deleted event name and payload', function () {
        $event = new DepartmentDeleted(makeDepartment('d-3'));

        expect($event->department->id)->toBe('d-3');
        expect($event->eventName())->toBe('department.deleted');
        expect($event->toPayload())->toHaveKey('department_id');
    });
});

describe('event dispatch via actions', function () {
    it('department created event is dispatched via action', function () {
        Event::fake([DepartmentCreated::class]);

        app(CreateDepartmentAction::class)->execute([
            'name' => 'RPL',
        ]);

        Event::assertDispatched(DepartmentCreated::class);
    });

    it('department deleted event is dispatched via action', function () {
        $department = Department::factory()->create();

        Event::fake([DepartmentDeleted::class]);

        app(DeleteDepartmentAction::class)->execute($department);

        Event::assertDispatched(DepartmentDeleted::class);
    });
});
