<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
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
    expect($request->registration_id)->not->toBeNull();
    expect($request->start_date)->not->toBeNull();
});

test('absence request defaults to pending status', function () {
    $request = AbsenceRequest::factory()->create();

    expect($request->status)->toBe(AbsenceRequestStatus::PENDING);
});

test('absence request belongs to user', function () {
    $user = User::factory()->create();
    $request = AbsenceRequest::factory()->create(['user_id' => $user->id]);

    expect($request->user)->toBeInstanceOf(User::class);
    expect($request->user->id)->toBe($user->id);
});

test('absence request belongs to registration', function () {
    $registration = Registration::factory()->create();
    $request = AbsenceRequest::factory()->create(['registration_id' => $registration->id]);

    expect($request->registration)->toBeInstanceOf(Registration::class);
    expect($request->registration->id)->toBe($registration->id);
});

test('absence request casts status to enum', function () {
    $request = AbsenceRequest::factory()->create(['status' => AbsenceRequestStatus::APPROVED]);

    expect($request->status)->toBeInstanceOf(AbsenceRequestStatus::class);
    expect($request->status)->toBe(AbsenceRequestStatus::APPROVED);
});

test('absence request casts reason_type to enum', function () {
    $request = AbsenceRequest::factory()->create(['reason_type' => AbsenceReasonType::SICK]);

    expect($request->reason_type)->toBeInstanceOf(AbsenceReasonType::class);
    expect($request->reason_type)->toBe(AbsenceReasonType::SICK);
});

test('absence request casts dates to date instances', function () {
    $request = AbsenceRequest::factory()->create();

    expect($request->start_date)->toBeInstanceOf(Carbon::class);
    expect($request->end_date)->toBeInstanceOf(Carbon::class);
});

test('absence request processor belongs to user', function () {
    $processor = User::factory()->create();
    $request = AbsenceRequest::factory()->create([
        'processed_by' => $processor->id,
        'status' => AbsenceRequestStatus::APPROVED,
    ]);

    expect($request->processor)->toBeInstanceOf(User::class);
    expect($request->processor->id)->toBe($processor->id);
});

test('absence request casts processed_at to datetime', function () {
    $request = AbsenceRequest::factory()->create([
        'processed_at' => now(),
        'status' => AbsenceRequestStatus::APPROVED,
    ]);

    expect($request->processed_at)->toBeInstanceOf(Carbon::class);
});

test('absence request returns AbsenceRequestStatus entity', function () {
    $request = AbsenceRequest::factory()->create();

    $status = $request->asAbsenceRequestStatus();

    expect($status)->toBeInstanceOf(App\Journals\AbsenceRequest\Entities\AbsenceRequestStatus::class);
});

test('absence request fillable attributes are mass assignable', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create();

    $request = AbsenceRequest::create([
        'user_id' => $user->id,
        'registration_id' => $registration->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'reason_type' => AbsenceReasonType::EMERGENCY->value,
        'reason_description' => 'Family emergency.',
        'status' => AbsenceRequestStatus::PENDING->value,
    ]);

    expect($request->reason_type)->toBe(AbsenceReasonType::EMERGENCY);
    expect($request->reason_description)->toBe('Family emergency.');
});

test('absence request uses AbsenceRequestFactory', function () {
    $request = AbsenceRequest::factory()->create();

    expect($request)->toBeInstanceOf(AbsenceRequest::class);
});
