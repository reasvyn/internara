<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Guidance\SupervisionLog\Actions\CreateSupervisionLogAction;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates supervision log by teacher', function () {
    $teacher = User::factory()->create();
    $registration = Registration::factory()->create();

    $log = app(CreateSupervisionLogAction::class)->execute(
        $teacher,
        $registration->id,
        ['topic' => 'Guidance Session', 'notes' => 'Discussed progress'],
    );

    expect($log)->toBeInstanceOf(SupervisionLog::class);
    expect($log->is_verified)->toBeTrue();
});

test('creates supervision log by supervisor', function () {
    $supervisor = User::factory()->create();
    $registration = Registration::factory()->create();

    $log = app(CreateSupervisionLogAction::class)->execute(
        $supervisor,
        $registration->id,
        ['topic' => 'Mentoring', 'notes' => 'On-site visit'],
    );

    expect($log->is_verified)->toBeFalse();
});
