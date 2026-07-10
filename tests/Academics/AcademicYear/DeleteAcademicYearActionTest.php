<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Actions\DeleteAcademicYearAction;
use App\Academics\AcademicYear\Events\AcademicYearDeleted;
use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Exceptions\RejectedException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

test('deletes inactive academic year without related records', function () {
    $year = AcademicYear::factory()->create(['is_active' => false]);
    $action = app(DeleteAcademicYearAction::class);

    $action->execute($year);

    $this->assertDatabaseMissing('academic_years', ['id' => $year->id]);
});

test('dispatches AcademicYearDeleted event on delete', function () {
    $year = AcademicYear::factory()->create(['is_active' => false]);
    $action = app(DeleteAcademicYearAction::class);

    $action->execute($year);

    Event::assertDispatched(
        AcademicYearDeleted::class,
        fn ($event) => $event->academicYear->id === $year->id,
    );
});

test('cannot delete active academic year', function () {
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $action = app(DeleteAcademicYearAction::class);

    expect(fn () => $action->execute($year))->toThrow(RejectedException::class);

    $this->assertModelExists($year);
});
