<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement\Actions\RejectPlacementChangeAction;
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

test('rejects pending placement change request', function () {
    $internship = Internship::factory()->create();
    $fromPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $toPlacement = Placement::factory()->create(['internship_id' => $internship->id]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);
    $request = PlacementChangeRequest::factory()->create([
        'registration_id' => $registration->id,
        'from_placement_id' => $fromPlacement->id,
        'to_placement_id' => $toPlacement->id,
    ]);

    app(RejectPlacementChangeAction::class)->execute($request, 'Placement quota not available');

    expect($request->fresh()->status->value)->toBe('rejected');
    expect($request->fresh()->rejection_reason)->toBe('Placement quota not available');
    expect($request->fresh()->processed_by)->toBe($this->admin->id);
});

test('throws exception when rejecting already processed request', function () {
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

    expect(fn () => app(RejectPlacementChangeAction::class)->execute($request, 'Too late'))
        ->toThrow(RejectedException::class);
});
