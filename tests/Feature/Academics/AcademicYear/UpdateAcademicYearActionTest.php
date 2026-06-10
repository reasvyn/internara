<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Actions\UpdateAcademicYearAction;
use App\Academics\AcademicYear\Models\AcademicYear;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates academic year name', function () {
    $year = AcademicYear::factory()->create(['name' => 'Old Name']);
    $action = app(UpdateAcademicYearAction::class);

    $action->execute($year, ['name' => 'New Name']);

    expect($year->fresh()->name)->toBe('New Name');
});

test('updates academic year dates', function () {
    $year = AcademicYear::factory()->create();
    $action = app(UpdateAcademicYearAction::class);

    $action->execute($year, [
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    $updated = $year->fresh();
    expect($updated->start_date->format('Y-m-d'))->toBe('2025-01-01');
    expect($updated->end_date->format('Y-m-d'))->toBe('2025-12-31');
});
