<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement\Actions\RequestPlacementChangeAction;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Placement\Models\PlacementChangeRequest;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('requests placement change with valid data', function () {
    $internship = Internship::factory()->create();
    $fromPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $toPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id, 'placement_id' => $fromPlacement->id]);
    $user = User::factory()->create();

    $request = app(RequestPlacementChangeAction::class)->execute($registration, [
        'to_placement_id' => $toPlacement->id,
        'reason' => 'Better opportunity at new placement',
        'requested_by' => $user->id,
    ]);

    expect($request)->toBeInstanceOf(PlacementChangeRequest::class);
    expect($request->status->value)->toBe('pending');
    expect($request->from_placement_id)->toBe($fromPlacement->id);
    expect($request->to_placement_id)->toBe($toPlacement->id);
});

test('throws exception when pending change request already exists', function () {
    $internship = Internship::factory()->create();
    $fromPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $toPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id, 'placement_id' => $fromPlacement->id]);
    $user = User::factory()->create();

    app(RequestPlacementChangeAction::class)->execute($registration, [
        'to_placement_id' => $toPlacement->id,
        'reason' => 'First request',
        'requested_by' => $user->id,
    ]);

    expect(fn () => app(RequestPlacementChangeAction::class)->execute($registration, [
        'to_placement_id' => $toPlacement->id,
        'reason' => 'Second request',
        'requested_by' => $user->id,
    ]))->toThrow(RejectedException::class);
});

test('throws validation exception when to_placement_id is missing', function () {
    $internship = Internship::factory()->create();
    $registration = Registration::factory()->create();

    expect(fn () => app(RequestPlacementChangeAction::class)->execute($registration, [
        'reason' => 'Some reason',
        'requested_by' => User::factory()->create()->id,
    ]))->toThrow(ValidationException::class);
});
