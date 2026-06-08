<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Actions\BulkDeleteAcademicYearsAction;
use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Exceptions\RejectedException;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

test('bulk deletes multiple inactive years', function () {
    $years = AcademicYear::factory(3)->create(['is_active' => false]);
    $ids = $years->pluck('id')->toArray();
    $action = app(BulkDeleteAcademicYearsAction::class);

    $count = $action->execute($ids);

    expect($count)->toBe(3);
    foreach ($years as $year) {
        $this->assertDatabaseMissing("academic_years", ["id" => $year->id]);
    }
});

test('bulk delete returns 0 for empty ids', function () {
    $action = app(BulkDeleteAcademicYearsAction::class);

    $count = $action->execute([]);

    expect($count)->toBe(0);
});

test('bulk delete throws if any year is active', function () {
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $action = app(BulkDeleteAcademicYearsAction::class);

    expect(fn () => $action->execute([$year->id]))->toThrow(RejectedException::class);

    $this->assertDatabaseHas("academic_years", ["id" => $year->id]);
});