<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Actions\VerifyRegistrationAction;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('verifies pending registration and places student', function () {
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create(['internship_id' => $internship->id, 'quota' => 10, 'filled_quota' => 0]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id, 'status' => 'pending']);

    $result = app(VerifyRegistrationAction::class)->execute($registration->id, [
        'placement_id' => $placement->id,
    ]);

    expect($result->status)->toBe('active');
    expect((int) $placement->fresh()->filled_quota)->toBe(1);
});

test('throws exception when registration is not pending', function () {
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create(['internship_id' => $internship->id]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id, 'status' => 'active']);

    expect(fn () => app(VerifyRegistrationAction::class)->execute($registration->id, [
        'placement_id' => $placement->id,
    ]))->toThrow(RejectedException::class);
});

test('throws exception when placement quota is full', function () {
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->full()->create(['internship_id' => $internship->id]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id, 'status' => 'pending']);

    expect(fn () => app(VerifyRegistrationAction::class)->execute($registration->id, [
        'placement_id' => $placement->id,
    ]))->toThrow(RejectedException::class);
});

test('fails with non-existent registration', function () {
    expect(fn () => app(VerifyRegistrationAction::class)->execute('non-existent-id', [
        'placement_id' => 'some-id',
    ]))->toThrow(ModelNotFoundException::class);
});
