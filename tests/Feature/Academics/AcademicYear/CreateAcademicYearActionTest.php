<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Actions\CreateAcademicYearAction;
use App\Academics\AcademicYear\Models\AcademicYear;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('creates academic year with valid data', function () {
    $action = app(CreateAcademicYearAction::class);

    $year = $action->execute([
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
    ]);

    expect($year)->toBeInstanceOf(AcademicYear::class);
    $this->assertModelExists($year);
    expect($year->name)->toBe('2025/2026');
    expect($year->is_active)->toBeFalse();
});

test('creates academic year as active when specified', function () {
    $action = app(CreateAcademicYearAction::class);

    $year = $action->execute([
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);

    expect($year->is_active)->toBeTrue();
});

test('rejects duplicate name', function () {
    AcademicYear::factory()->create(['name' => '2025/2026']);

    $action = app(CreateAcademicYearAction::class);

    expect(fn () => $action->execute([
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
    ]))->toThrow(ValidationException::class);
});

test('rejects end date before start date', function () {
    $action = app(CreateAcademicYearAction::class);

    expect(fn () => $action->execute([
        'name' => 'Invalid',
        'start_date' => '2026-01-01',
        'end_date' => '2025-12-31',
    ]))->toThrow(ValidationException::class);
});
