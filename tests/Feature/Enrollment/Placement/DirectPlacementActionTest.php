<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement\Actions\DirectPlacementAction;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('directly places student into placement', function () {
    $student = User::factory()->create();
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create(['internship_id' => $internship->id, 'quota' => 10, 'filled_quota' => 0]);

    $registration = app(DirectPlacementAction::class)->execute($student, [
        'placement_id' => $placement->id,
        'academic_year' => '2025/2026',
    ]);

    expect($registration)->toBeInstanceOf(Registration::class);
    expect($registration->status)->toBe('active');
    expect((int) $placement->fresh()->filled_quota)->toBe(1);
});

test('throws exception when placement is full', function () {
    $student = User::factory()->create();
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->full()->create(['internship_id' => $internship->id]);

    expect(fn () => app(DirectPlacementAction::class)->execute($student, [
        'placement_id' => $placement->id,
    ]))->toThrow(RejectedException::class);
});
