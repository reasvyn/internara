<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\AcademicYear\Actions\CreateAcademicYearAction;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearCreated;
use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| XW6F5 — Academic Year Management — CreateAcademicYearAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('XW6F5: CreateAcademicYearAction', function (): void {
    test('XW6F5-FR-AY22: validates name is required and unique', function (): void {
        AcademicYear::factory()->create(['name' => '2025/2026']);

        expect(fn () => app(CreateAcademicYearAction::class)->execute([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]))->toThrow(ValidationException::class);

        expect(fn () => app(CreateAcademicYearAction::class)->execute([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]))->toThrow(ValidationException::class);
    });

    test('XW6F5-FR-AY22: validates name max 50 characters', function (): void {
        expect(fn () => app(CreateAcademicYearAction::class)->execute([
            'name' => str_repeat('a', 51),
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]))->toThrow(ValidationException::class);
    });

    test('XW6F5-FR-AY22: validates end_date must be after start_date', function (): void {
        expect(fn () => app(CreateAcademicYearAction::class)->execute([
            'name' => '2025/2026',
            'start_date' => '2026-06-30',
            'end_date' => '2025-07-01',
        ]))->toThrow(ValidationException::class);
    });

    test('XW6F5-FR-AY22: validates start_date and end_date are required', function (): void {
        expect(fn () => app(CreateAcademicYearAction::class)->execute([
            'name' => 'No Dates',
        ]))->toThrow(ValidationException::class);

        expect(fn () => app(CreateAcademicYearAction::class)->execute([
            'name' => 'Missing End',
            'start_date' => '2025-07-01',
        ]))->toThrow(ValidationException::class);
    });

    test('XW6F5-FR-AY19/FN-AY20: auto-activates when creating the first academic year', function (): void {
        $year = app(CreateAcademicYearAction::class)->execute([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        // Spec FR-AY19/FR-AY20: the first academic year must be auto-activated
        expect($year->is_active)->toBeTrue();
    });

    test('XW6F5-FR-AY21: creates subsequent years as inactive', function (): void {
        AcademicYear::factory()->create();

        $year = app(CreateAcademicYearAction::class)->execute([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        expect($year->is_active)->toBeFalse();
    });

    test('XW6F5-FR-AY23: dispatches AcademicYearCreated event', function (): void {
        Event::fake([AcademicYearCreated::class]);

        $year = app(CreateAcademicYearAction::class)->execute([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        Event::assertDispatched(AcademicYearCreated::class, function ($event) use ($year) {
            return $event->academicYear->id === $year->id;
        });
    });
});
