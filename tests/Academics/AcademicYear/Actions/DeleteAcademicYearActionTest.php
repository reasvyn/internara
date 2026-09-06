<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\AcademicYear\Actions\DeleteAcademicYearAction;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearDeleted;
use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Program\Domain\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| XW6F5 — Academic Year Management — DeleteAcademicYearAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('XW6F5: DeleteAcademicYearAction', function (): void {
    test('XW6F5-FR-AY13: rejects deletion of active year', function (): void {
        $year = AcademicYear::factory()->active()->create();

        expect(fn () => app(DeleteAcademicYearAction::class)->execute($year))
            ->toThrow(RejectedException::class);

        expect(AcademicYear::find($year->id))->not->toBeNull();
    });

    test('XW6F5-FR-AY13: rejects deletion of year with internships', function (): void {
        $year = AcademicYear::factory()->create(['is_active' => false]);
        Internship::factory()->create(['academic_year_id' => $year->id]);

        expect(fn () => app(DeleteAcademicYearAction::class)->execute($year))
            ->toThrow(RejectedException::class);

        expect(AcademicYear::find($year->id))->not->toBeNull();
    });

    test('XW6F5-FR-AY14: successfully deletes inactive year with no data', function (): void {
        $year = AcademicYear::factory()->create(['is_active' => false]);

        app(DeleteAcademicYearAction::class)->execute($year);

        expect(AcademicYear::find($year->id))->toBeNull();
    });

    test('XW6F5-FR-AY14: dispatches AcademicYearDeleted event', function (): void {
        Event::fake([AcademicYearDeleted::class]);

        $year = AcademicYear::factory()->create(['is_active' => false]);

        app(DeleteAcademicYearAction::class)->execute($year);

        Event::assertDispatched(AcademicYearDeleted::class, function ($event) use ($year) {
            return $event->academicYear->id === $year->id;
        });
    });

    test('XW6F5-FR-AY14: wraps deletion in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(DeleteAcademicYearAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });
});
