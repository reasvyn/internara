<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Events\AcademicYearActivated;
use App\Academics\AcademicYear\Events\AcademicYearCreated;
use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('academic year created event is dispatched via action', function () {
    Event::fake([AcademicYearCreated::class]);

    app(\App\Academics\AcademicYear\Actions\CreateAcademicYearAction::class)->execute([
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
    ]);

    Event::assertDispatched(AcademicYearCreated::class);
});

test('academic year activated event is dispatched', function () {
    $year = \App\Academics\AcademicYear\Models\AcademicYear::factory()->create();

    Event::fake([AcademicYearActivated::class]);

    app(\App\Academics\AcademicYear\Actions\ActivateAcademicYearAction::class)->execute($year);

    Event::assertDispatched(AcademicYearActivated::class);
});

test('department created event is dispatched via action', function () {
    Event::fake([DepartmentCreated::class]);

    app(\App\Academics\Department\Actions\CreateDepartmentAction::class)->execute([
        'name' => 'RPL',
    ]);

    Event::assertDispatched(DepartmentCreated::class);
});

test('department deleted event is dispatched via action', function () {
    $department = \App\Academics\Department\Models\Department::factory()->create();

    Event::fake([DepartmentDeleted::class]);

    app(\App\Academics\Department\Actions\DeleteDepartmentAction::class)->execute($department);

    Event::assertDispatched(DepartmentDeleted::class);
});