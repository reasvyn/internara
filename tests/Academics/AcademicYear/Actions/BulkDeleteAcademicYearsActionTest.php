<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\AcademicYear\Actions\BulkDeleteAcademicYearsAction;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearDeleted;
use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Program\Domain\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| XW6F5 — Academic Year Management — BulkDeleteAcademicYearsAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('XW6F5: BulkDeleteAcademicYearsAction', function (): void {
    test('XW6F5-FR-AY15: returns 0 for empty array', function (): void {
        $result = app(BulkDeleteAcademicYearsAction::class)->execute([]);

        expect($result)->toBe(0);
    });

    test('XW6F5-FR-AY15: returns 0 when no matching IDs exist', function (): void {
        $result = app(BulkDeleteAcademicYearsAction::class)->execute(['non-existent-id-1', 'non-existent-id-2']);

        expect($result)->toBe(0);
    });

    test('XW6F5-FR-AY16/AY17: rejects if any year is active', function (): void {
        $active = AcademicYear::factory()->active()->create();
        $inactive = AcademicYear::factory()->create(['is_active' => false]);

        expect(fn () => app(BulkDeleteAcademicYearsAction::class)->execute([$active->id, $inactive->id]))
            ->toThrow(RejectedException::class);

        // Neither year should be deleted — all-or-nothing (DD-3)
        expect(AcademicYear::find($active->id))->not->toBeNull();
        expect(AcademicYear::find($inactive->id))->not->toBeNull();
    });

    test('XW6F5-FR-AY16/AY17: rejects if any year has internships', function (): void {
        $year1 = AcademicYear::factory()->create(['is_active' => false]);
        $year2 = AcademicYear::factory()->create(['is_active' => false]);
        Internship::factory()->create(['academic_year_id' => $year1->id]);

        expect(fn () => app(BulkDeleteAcademicYearsAction::class)->execute([$year1->id, $year2->id]))
            ->toThrow(RejectedException::class);

        // All-or-nothing: neither year deleted when one has data
        expect(AcademicYear::find($year1->id))->not->toBeNull();
        expect(AcademicYear::find($year2->id))->not->toBeNull();
    });

    test('XW6F5-FR-AY18: successfully deletes all valid years and returns count', function (): void {
        $year1 = AcademicYear::factory()->create(['is_active' => false]);
        $year2 = AcademicYear::factory()->create(['is_active' => false]);
        $year3 = AcademicYear::factory()->create(['is_active' => false]);

        $result = app(BulkDeleteAcademicYearsAction::class)->execute([$year1->id, $year2->id, $year3->id]);

        expect($result)->toBe(3);
        expect(AcademicYear::find($year1->id))->toBeNull();
        expect(AcademicYear::find($year2->id))->toBeNull();
        expect(AcademicYear::find($year3->id))->toBeNull();
    });

    test('XW6F5-FR-AY18: dispatches AcademicYearDeleted event per deleted year', function (): void {
        Event::fake([AcademicYearDeleted::class]);

        $year1 = AcademicYear::factory()->create(['is_active' => false]);
        $year2 = AcademicYear::factory()->create(['is_active' => false]);

        app(BulkDeleteAcademicYearsAction::class)->execute([$year1->id, $year2->id]);

        Event::assertDispatched(AcademicYearDeleted::class, 2);
        Event::assertDispatched(AcademicYearDeleted::class, function ($event) use ($year1) {
            return $event->academicYear->id === $year1->id;
        });
        Event::assertDispatched(AcademicYearDeleted::class, function ($event) use ($year2) {
            return $event->academicYear->id === $year2->id;
        });
    });
});
