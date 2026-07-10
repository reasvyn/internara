<?php

declare(strict_types=1);

use App\Enrollment\Placement\Enums\PlacementChangeStatus;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Placement\Models\PlacementChangeRequest;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('placement change request has fillable attributes', function () {
    $request = new PlacementChangeRequest;

    expect($request->getFillable())->toContain('registration_id', 'from_placement_id', 'to_placement_id', 'reason', 'requested_by', 'status', 'processed_by', 'processed_at', 'rejection_reason');
});

test('placement change request defaults to pending', function () {
    $request = PlacementChangeRequest::factory()->create();

    expect($request->status)->toBeInstanceOf(PlacementChangeStatus::class);
    expect($request->status->value)->toBe('pending');
});

test('placement change request belongs to registration', function () {
    $registration = Registration::factory()->create();
    $request = PlacementChangeRequest::factory()->create(['registration_id' => $registration->id]);

    expect($request->registration)->toBeInstanceOf(Registration::class);
    expect($request->registration->id)->toBe($registration->id);
});

test('placement change request belongs to from placement', function () {
    $placement = Placement::factory()->create();
    $request = PlacementChangeRequest::factory()->create(['from_placement_id' => $placement->id]);

    expect($request->fromPlacement)->toBeInstanceOf(Placement::class);
});

test('placement change request belongs to to placement', function () {
    $placement = Placement::factory()->create();
    $request = PlacementChangeRequest::factory()->create(['to_placement_id' => $placement->id]);

    expect($request->toPlacement)->toBeInstanceOf(Placement::class);
});

test('placement change request belongs to requester', function () {
    $user = User::factory()->create();
    $request = PlacementChangeRequest::factory()->create(['requested_by' => $user->id]);

    expect($request->requester)->toBeInstanceOf(User::class);
    expect($request->requester->id)->toBe($user->id);
});

test('placement change status isTerminal returns true for approved', function () {
    expect(PlacementChangeStatus::APPROVED->isTerminal())->toBeTrue();
});

test('placement change status isTerminal returns true for rejected', function () {
    expect(PlacementChangeStatus::REJECTED->isTerminal())->toBeTrue();
});

test('placement change status isTerminal returns false for pending', function () {
    expect(PlacementChangeStatus::PENDING->isTerminal())->toBeFalse();
});
