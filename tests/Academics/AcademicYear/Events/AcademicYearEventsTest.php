<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Actions\ActivateAcademicYearAction;
use App\Academics\AcademicYear\Actions\CreateAcademicYearAction;
use App\Academics\AcademicYear\Events\AcademicYearActivated;
use App\Academics\AcademicYear\Events\AcademicYearCreated;
use App\Academics\AcademicYear\Events\AcademicYearDeleted;
use App\Academics\AcademicYear\Events\AcademicYearUpdated;
use App\Academics\AcademicYear\Models\AcademicYear;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

function makeAcademicYear(string $id): AcademicYear
{
    $model = new class extends AcademicYear {};
    $model->forceFill(['id' => $id]);

    return $model;
}

beforeEach(function () {
    Cache::flush();
});

describe('event construction', function () {
    it('academic year created event name and payload', function () {
        $event = new AcademicYearCreated(makeAcademicYear('ay-1'));

        expect($event->academicYear->id)->toBe('ay-1');
        expect($event->eventName())->toBe('academic_year.created');
        expect($event->toPayload())->toHaveKey('academicYear_id');
    });

    it('academic year updated event name and payload', function () {
        $event = new AcademicYearUpdated(makeAcademicYear('ay-2'));

        expect($event->academicYear->id)->toBe('ay-2');
        expect($event->eventName())->toBe('academic_year.updated');
        expect($event->toPayload())->toHaveKey('academicYear_id');
    });

    it('academic year deleted event name and payload', function () {
        $event = new AcademicYearDeleted(makeAcademicYear('ay-3'));

        expect($event->academicYear->id)->toBe('ay-3');
        expect($event->eventName())->toBe('academic_year.deleted');
        expect($event->toPayload())->toHaveKey('academicYear_id');
    });

    it('academic year activated event name and payload', function () {
        $previous = makeAcademicYear('ay-0');
        $event = new AcademicYearActivated(makeAcademicYear('ay-1'), $previous);

        expect($event->academicYear->id)->toBe('ay-1');
        expect($event->previousActive->id)->toBe('ay-0');
        expect($event->eventName())->toBe('academic_year.activated');
        expect($event->toPayload())->toHaveKey('academicYear_id');
        expect($event->toPayload())->toHaveKey('previousActive_id');
    });

    it('academic year activated event allows null previous active', function () {
        $event = new AcademicYearActivated(makeAcademicYear('ay-1'));

        expect($event->previousActive)->toBeNull();
        expect($event->toPayload())->toHaveKey('academicYear_id');
        expect($event->toPayload())->not->toHaveKey('previousActive_id');
    });
});

describe('event dispatch via actions', function () {
    it('academic year created event is dispatched via action', function () {
        Event::fake([AcademicYearCreated::class]);

        app(CreateAcademicYearAction::class)->execute([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        Event::assertDispatched(AcademicYearCreated::class);
    });

    it('academic year activated event is dispatched', function () {
        $year = AcademicYear::factory()->create();

        Event::fake([AcademicYearActivated::class]);

        app(ActivateAcademicYearAction::class)->execute($year);

        Event::assertDispatched(AcademicYearActivated::class);
    });
});
