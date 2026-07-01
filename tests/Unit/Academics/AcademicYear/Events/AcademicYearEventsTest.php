<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Events\AcademicYearActivated;
use App\Academics\AcademicYear\Events\AcademicYearCreated;
use App\Academics\AcademicYear\Events\AcademicYearDeleted;
use App\Academics\AcademicYear\Events\AcademicYearUpdated;
use App\Academics\AcademicYear\Models\AcademicYear;

function makeAcademicYear(string $id): AcademicYear
{
    $model = new class extends AcademicYear {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('academic year created event name and payload', function () {
    $event = new AcademicYearCreated(makeAcademicYear('ay-1'));

    expect($event->academicYear->id)->toBe('ay-1');
    expect($event->eventName())->toBe('academic_year.created');
    expect($event->toPayload())->toHaveKey('academicYear_id');
});

test('academic year updated event name and payload', function () {
    $event = new AcademicYearUpdated(makeAcademicYear('ay-2'));

    expect($event->academicYear->id)->toBe('ay-2');
    expect($event->eventName())->toBe('academic_year.updated');
    expect($event->toPayload())->toHaveKey('academicYear_id');
});

test('academic year deleted event name and payload', function () {
    $event = new AcademicYearDeleted(makeAcademicYear('ay-3'));

    expect($event->academicYear->id)->toBe('ay-3');
    expect($event->eventName())->toBe('academic_year.deleted');
    expect($event->toPayload())->toHaveKey('academicYear_id');
});

test('academic year activated event name and payload', function () {
    $previous = makeAcademicYear('ay-0');
    $event = new AcademicYearActivated(makeAcademicYear('ay-1'), $previous);

    expect($event->academicYear->id)->toBe('ay-1');
    expect($event->previousActive->id)->toBe('ay-0');
    expect($event->eventName())->toBe('academic_year.activated');
    expect($event->toPayload())->toHaveKey('academicYear_id');
    expect($event->toPayload())->toHaveKey('previousActive_id');
});

test('academic year activated event allows null previous active', function () {
    $event = new AcademicYearActivated(makeAcademicYear('ay-1'));

    expect($event->previousActive)->toBeNull();
    expect($event->toPayload())->toHaveKey('academicYear_id');
    expect($event->toPayload())->not->toHaveKey('previousActive_id');
});
