<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement\Actions\ApprovePlacementChangeAction;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Placement\Models\PlacementChangeRequest;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    test()->actingAs($this->admin);
});

test('approves pending placement change request', function () {
    $internship = Internship::factory()->create();
    $fromPlacement = Placement::factory()->create(['internship_id' => $internship->id, 'filled_quota' => 5]);
    $toPlacement = Placement::factory()->create(['internship_id' => $internship->id, 'quota' => 10, 'filled_quota' => 3]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id, 'placement_id' => $fromPlacement->id]);
    $request = PlacementChangeRequest::factory()->create([
        'registration_id' => $registration->id,
        'from_placement_id' => $fromPlacement->id,
        'to_placement_id' => $toPlacement->id,
    ]);

    app(ApprovePlacementChangeAction::class)->execute($request);

    expect($request->fresh()->status->value)->toBe('approved');
    expect((int) $fromPlacement->fresh()->filled_quota)->toBe(4);
    expect((int) $toPlacement->fresh()->filled_quota)->toBe(4);
    expect($registration->fresh()->placement_id)->toBe($toPlacement->id);
});

test('throws exception when request already in terminal state', function () {
    $internship = Internship::factory()->create();
    $fromPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $toPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);
    $request = PlacementChangeRequest::factory()->create([
        'registration_id' => $registration->id,
        'from_placement_id' => $fromPlacement->id,
        'to_placement_id' => $toPlacement->id,
        'status' => 'approved',
    ]);

    expect(fn () => app(ApprovePlacementChangeAction::class)->execute($request))
        ->toThrow(RejectedException::class);
});

test('throws exception when target placement is full', function () {
    $internship = Internship::factory()->create();
    $fromPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $toPlacement = Placement::factory()->full()->create(['internship_id' => $internship->id]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id, 'placement_id' => $fromPlacement->id]);
    $request = PlacementChangeRequest::factory()->create([
        'registration_id' => $registration->id,
        'from_placement_id' => $fromPlacement->id,
        'to_placement_id' => $toPlacement->id,
    ]);

    expect(fn () => app(ApprovePlacementChangeAction::class)->execute($request))
        ->toThrow(RejectedException::class);
});
