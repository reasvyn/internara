<?php

declare(strict_types=1);

use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use App\Academics\Department\Events\DepartmentUpdated;
use App\Academics\Department\Models\Department;

function makeDepartment(string $id): Department
{
    $model = new class extends Department {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('department created event name and payload', function () {
    $event = new DepartmentCreated(makeDepartment('d-1'));

    expect($event->department->id)->toBe('d-1');
    expect($event->eventName())->toBe('department.created');
    expect($event->toPayload())->toHaveKey('department_id');
});

test('department updated event name and payload', function () {
    $event = new DepartmentUpdated(makeDepartment('d-2'));

    expect($event->department->id)->toBe('d-2');
    expect($event->eventName())->toBe('department.updated');
    expect($event->toPayload())->toHaveKey('department_id');
});

test('department deleted event name and payload', function () {
    $event = new DepartmentDeleted(makeDepartment('d-3'));

    expect($event->department->id)->toBe('d-3');
    expect($event->eventName())->toBe('department.deleted');
    expect($event->toPayload())->toHaveKey('department_id');
});
