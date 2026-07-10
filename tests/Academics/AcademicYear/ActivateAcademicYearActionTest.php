<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Actions\ActivateAcademicYearAction;
use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Exceptions\RejectedException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('activates inactive academic year', function () {
    $year = AcademicYear::factory()->create(['is_active' => false]);
    $action = app(ActivateAcademicYearAction::class);

    $result = $action->execute($year);

    expect($result->is_active)->toBeTrue();
    expect($year->fresh()->is_active)->toBeTrue();
});

test('deactivates previously active year on activation', function () {
    $old = AcademicYear::factory()->create(['is_active' => true]);
    $new = AcademicYear::factory()->create(['is_active' => false]);
    $action = app(ActivateAcademicYearAction::class);

    $action->execute($new);

    expect($old->fresh()->is_active)->toBeFalse();
    expect($new->fresh()->is_active)->toBeTrue();
});

test('cannot activate already active academic year', function () {
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $action = app(ActivateAcademicYearAction::class);

    expect(fn () => $action->execute($year))->toThrow(RejectedException::class);

    expect($year->fresh()->is_active)->toBeTrue();
});
