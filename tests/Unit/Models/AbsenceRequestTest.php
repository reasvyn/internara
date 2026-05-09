<?php

declare(strict_types=1);

use App\Enums\Attendance\AbsenceRequestStatus;
use App\Models\AbsenceRequest;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $request = AbsenceRequest::factory()->create();

    expect($request)->toBeInstanceOf(AbsenceRequest::class)
        ->and($request->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $request = AbsenceRequest::factory()->create([
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-03',
        'status' => AbsenceRequestStatus::APPROVED,
        'processed_at' => now(),
    ]);

    expect($request->start_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($request->end_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($request->status)->toBe(AbsenceRequestStatus::APPROVED)
        ->and($request->processed_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to user', function () {
    $user = User::factory()->create();
    $request = AbsenceRequest::factory()->create(['user_id' => $user->id]);

    expect($request->user)->toBeInstanceOf(User::class)
        ->and($request->user->id)->toBe($user->id);
});

it('belongs to registration', function () {
    $registration = Registration::factory()->create();
    $request = AbsenceRequest::factory()->create(['registration_id' => $registration->id]);

    expect($request->registration)->toBeInstanceOf(Registration::class)
        ->and($request->registration->id)->toBe($registration->id);
});

it('belongs to processor', function () {
    $processor = User::factory()->create();
    $request = AbsenceRequest::factory()->create(['processed_by' => $processor->id]);

    expect($request->processor)->toBeInstanceOf(User::class)
        ->and($request->processor->id)->toBe($processor->id);
});

it('delegates status checks to entity', function () {
    $request = AbsenceRequest::factory()->create(['status' => AbsenceRequestStatus::PENDING]);
    expect($request->asAbsenceRequestStatus()->isPending())->toBeTrue();

    $request->update(['status' => AbsenceRequestStatus::APPROVED]);
    expect($request->asAbsenceRequestStatus()->isProcessed())->toBeTrue();
});
