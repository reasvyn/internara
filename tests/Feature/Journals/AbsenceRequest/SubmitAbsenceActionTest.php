<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\AbsenceRequest\Actions\SubmitAbsenceAction;
use App\Journals\AbsenceRequest\Enums\AbsenceReasonType;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('submits absence request with valid data', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $request = app(SubmitAbsenceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'start_date' => now()->addDay()->toDateString(),
        'reason_type' => 'sick',
        'reason_description' => 'Feeling unwell.',
    ]);

    expect($request)->toBeInstanceOf(AbsenceRequest::class);
    $this->assertDatabaseHas('absence_requests', ['id' => $request->id]);
    expect($request->user_id)->toBe($user->id);
    expect($request->registration_id)->toBe($registration->id);
    expect($request->reason_type)->toBe(AbsenceReasonType::SICK);
    expect($request->reason_description)->toBe('Feeling unwell.');
    expect($request->status)->toBe(AbsenceRequestStatus::PENDING);
});

test('submits absence request with end date same as start when not provided', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);
    $startDate = now()->addDay()->toDateString();

    $request = app(SubmitAbsenceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'start_date' => $startDate,
        'reason_type' => 'permission',
    ]);

    expect($request->start_date->toDateString())->toBe($startDate);
    expect($request->end_date->toDateString())->toBe($startDate);
});

test('submits absence request with explicit end date', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $request = app(SubmitAbsenceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
        'reason_type' => 'emergency',
        'reason_description' => 'Family emergency.',
    ]);

    expect($request->end_date->toDateString())->toBe(now()->addDays(3)->toDateString());
});

test('submits absence request without description', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $request = app(SubmitAbsenceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'start_date' => now()->addDay()->toDateString(),
        'reason_type' => 'other',
    ]);

    expect($request->reason_description)->toBeNull();
});

test('submits absence request with other reason type', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $request = app(SubmitAbsenceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'start_date' => now()->addDay()->toDateString(),
        'reason_type' => 'other',
        'reason_description' => 'Personal matter.',
    ]);

    expect($request->reason_type)->toBe(AbsenceReasonType::OTHER);
});
