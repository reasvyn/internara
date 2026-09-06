<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\AcademicYear\Actions\UpdateAcademicYearAction;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearUpdated;
use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| XW6F5 — Academic Year Management — UpdateAcademicYearAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('XW6F5: UpdateAcademicYearAction', function (): void {
    test('XW6F5-FR-AY24: persists name change', function (): void {
        $year = AcademicYear::factory()->create(['name' => '2025/2026']);

        $result = app(UpdateAcademicYearAction::class)->execute($year, [
            'name' => '2025/2026 (Revised)',
        ]);

        expect($result->name)->toBe('2025/2026 (Revised)');
        expect($year->fresh()->name)->toBe('2025/2026 (Revised)');
    });

    test('XW6F5-FR-AY24: persists date changes', function (): void {
        $year = AcademicYear::factory()->create([
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        $result = app(UpdateAcademicYearAction::class)->execute($year, [
            'start_date' => '2025-08-01',
            'end_date' => '2026-07-31',
        ]);

        expect($result->start_date->format('Y-m-d'))->toBe('2025-08-01');
        expect($result->end_date->format('Y-m-d'))->toBe('2026-07-31');
    });

    test('XW6F5-FR-AY24: persists partial updates without overwriting omitted fields', function (): void {
        $year = AcademicYear::factory()->create([
            'name' => 'Original Name',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        app(UpdateAcademicYearAction::class)->execute($year, ['name' => 'New Name']);

        $fresh = $year->fresh();
        expect($fresh->name)->toBe('New Name');
        expect($fresh->start_date->format('Y-m-d'))->toBe('2025-07-01');
        expect($fresh->end_date->format('Y-m-d'))->toBe('2026-06-30');
    });

    test('XW6F5-FR-AY24: wraps update in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(UpdateAcademicYearAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });

    test('XW6F5-FR-AY25: dispatches AcademicYearUpdated event', function (): void {
        Event::fake([AcademicYearUpdated::class]);

        $year = AcademicYear::factory()->create();

        app(UpdateAcademicYearAction::class)->execute($year, ['name' => 'Updated']);

        Event::assertDispatched(AcademicYearUpdated::class, function ($event) use ($year) {
            return $event->academicYear->id === $year->id;
        });
    });
});
