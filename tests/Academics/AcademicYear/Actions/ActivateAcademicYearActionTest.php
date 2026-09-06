<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\AcademicYear\Actions\ActivateAcademicYearAction;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearActivated;
use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Exceptions\RejectedException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| XW6F5 — Academic Year Management — ActivateAcademicYearAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('XW6F5: ActivateAcademicYearAction', function (): void {
    test('XW6F5-FR-AY9: rejects activation of already active year', function (): void {
        $year = AcademicYear::factory()->active()->create();

        expect(fn () => app(ActivateAcademicYearAction::class)->execute($year))
            ->toThrow(RejectedException::class);
    });

    test('XW6F5-FR-AY10: deactivates all currently active years', function (): void {
        $oldActive = AcademicYear::factory()->active()->create();
        $newYear = AcademicYear::factory()->create();

        app(ActivateAcademicYearAction::class)->execute($newYear);

        expect($oldActive->fresh()->is_active)->toBeFalse();
    });

    test('XW6F5-FR-AY11: activates the target year', function (): void {
        AcademicYear::factory()->active()->create();
        $newYear = AcademicYear::factory()->create();

        $result = app(ActivateAcademicYearAction::class)->execute($newYear);

        expect($result->is_active)->toBeTrue();
        expect($newYear->fresh()->is_active)->toBeTrue();
    });

    test('XW6F5-NFR-R1: atomic — only one year active after activation', function (): void {
        $oldActive = AcademicYear::factory()->active()->create();
        $newYear = AcademicYear::factory()->create();

        app(ActivateAcademicYearAction::class)->execute($newYear);

        $activeCount = AcademicYear::where('is_active', true)->count();
        expect($activeCount)->toBe(1);
        expect($oldActive->fresh()->is_active)->toBeFalse();
        expect($newYear->fresh()->is_active)->toBeTrue();
    });

    test('XW6F5-FR-AY12: dispatches AcademicYearActivated event', function (): void {
        Event::fake([AcademicYearActivated::class]);

        AcademicYear::factory()->active()->create();
        $newYear = AcademicYear::factory()->create();

        app(ActivateAcademicYearAction::class)->execute($newYear);

        Event::assertDispatched(AcademicYearActivated::class, function ($event) use ($newYear) {
            return $event->academicYear->id === $newYear->id;
        });
    });

    test('XW6F5-FR-AY11: can activate a year when no other year is active', function (): void {
        $year = AcademicYear::factory()->create(['is_active' => false]);

        $result = app(ActivateAcademicYearAction::class)->execute($year);

        expect($result->is_active)->toBeTrue();
        expect(AcademicYear::where('is_active', true)->count())->toBe(1);
    });
});
