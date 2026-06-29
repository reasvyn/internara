<?php

declare(strict_types=1);

use App\Journals\AbsenceRequest\Enums\AbsenceReasonType;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

test('absence request factory creates valid model', function () {
    $request = AbsenceRequest::factory()->create();

    expect($request)->toBeInstanceOf(AbsenceRequest::class);
    expect($request->user_id)->not->toBeNull();
    expect($request->date)->not->toBeNull();
});

test('absence request defaults to pending status', function () {
    $request = AbsenceRequest::factory()->create();

    expect($request->absence_status)->toBe(AbsenceRequestStatus::PENDING);
});

test('absence request belongs to user', function () {
    $user = User::factory()->create();
    $request = AbsenceRequest::factory()->create(['user_id' => $user->id]);

    expect($request->user)->toBeInstanceOf(User::class);
    expect($request->user->id)->toBe($user->id);
});

test('absence request casts absence_type to enum', function () {
    $request = AbsenceRequest::factory()->create(['absence_type' => AbsenceReasonType::SICK]);

    expect($request->absence_type)->toBeInstanceOf(AbsenceReasonType::class);
    expect($request->absence_type)->toBe(AbsenceReasonType::SICK);
});

test('absence request casts date to date instance', function () {
    $request = AbsenceRequest::factory()->create();

    expect($request->date)->toBeInstanceOf(Carbon::class);
});

test('absence request processor belongs to user', function () {
    $processor = User::factory()->create();
    $request = AbsenceRequest::factory()->create([
        'absence_processed_by' => $processor->id,
        'absence_status' => AbsenceRequestStatus::APPROVED,
    ]);

    expect($request->processor)->toBeInstanceOf(User::class);
    expect($request->processor->id)->toBe($processor->id);
});

test('absence request casts absence_processed_at to datetime', function () {
    $request = AbsenceRequest::factory()->create([
        'absence_processed_at' => now(),
        'absence_status' => AbsenceRequestStatus::APPROVED,
    ]);

    expect($request->absence_processed_at)->toBeInstanceOf(Carbon::class);
});

test('absence request fillable attributes are mass assignable', function () {
    $user = User::factory()->create();

    $registration = \App\Enrollment\Registration\Models\Registration::factory()->create(['student_id' => $user->id]);

    $request = AbsenceRequest::create([
        'user_id' => $user->id,
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'absence_type' => AbsenceReasonType::SICK->value,
        'absence_reason' => 'Family emergency.',
        'absence_status' => AbsenceRequestStatus::PENDING->value,
    ]);

    expect($request->absence_type)->toBe(AbsenceReasonType::SICK);
    expect($request->absence_reason)->toBe('Family emergency.');
});

test('absence request uses attendances table', function () {
    $request = AbsenceRequest::factory()->create();

    expect($request->getTable())->toBe('attendances');
});
