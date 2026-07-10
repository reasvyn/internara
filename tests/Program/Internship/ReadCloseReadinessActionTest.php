<?php

declare(strict_types=1);

use App\Assessment\Models\Assessment;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Actions\ReadCloseReadinessAction;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('all checks pass when no registrations exist', function () {
    $internship = Internship::factory()->create();
    $action = app(ReadCloseReadinessAction::class);

    $result = $action->execute($internship);

    expect($result['assessments']['passed'])->toBeTrue();
    expect($result['submissions']['passed'])->toBeTrue();
    expect($result['supervision_logs']['passed'])->toBeTrue();
    expect($result['attendance']['passed'])->toBeTrue();
    expect($result['certificates']['passed'])->toBeFalse();
    expect($result['certificates']['message'])->toBe('No certificates issued.');
});

test('returns correct structure for each check', function () {
    $internship = Internship::factory()->create();
    $action = app(ReadCloseReadinessAction::class);

    $result = $action->execute($internship);

    $expectedKeys = ['passed', 'total', 'pending', 'message'];
    foreach (['assessments', 'submissions', 'supervision_logs', 'attendance', 'certificates'] as $check) {
        expect($result[$check])->toHaveKeys($expectedKeys);
    }
});

test('reports pending assessments when some are not finalized', function () {
    $internship = Internship::factory()->create();
    $registration = Registration::factory()
        ->create(['internship_id' => $internship->id]);
    $registration->update(['status' => 'active']);
    Assessment::factory()->create([
        'registration_id' => $registration->id,
        'finalized_at' => null,
    ]);
    $action = app(ReadCloseReadinessAction::class);

    $result = $action->execute($internship);

    expect($result['assessments']['passed'])->toBeFalse();
    expect($result['assessments']['pending'])->toBe(1);
});
