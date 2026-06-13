<?php

declare(strict_types=1);

use App\Enrollment\Registration\Entities\RegistrationState;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

test('registration has fillable attributes', function () {
    $registration = new Registration;

    expect($registration->getFillable())->toContain('student_id', 'internship_id', 'placement_id', 'start_date', 'end_date', 'status', 'proposed_company_details');
});

test('registration casts dates', function () {
    $registration = Registration::factory()->create([
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
    ]);

    expect($registration->start_date)->toBeInstanceOf(Carbon::class);
    expect($registration->end_date)->toBeInstanceOf(Carbon::class);
});

test('registration casts proposed_company_details to array', function () {
    $registration = Registration::factory()->create();

    expect($registration->proposed_company_details)->toBeArray();
});

test('registration belongs to student', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['student_id' => $user->id]);

    expect($registration->student)->toBeInstanceOf(User::class);
    expect($registration->student->id)->toBe($user->id);
});

test('registration belongs to internship', function () {
    $internship = Internship::factory()->create();
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);

    expect($registration->internship)->toBeInstanceOf(Internship::class);
});

test('registration has default pending status', function () {
    $registration = Registration::factory()->create();

    expect($registration->status)->toBe('pending');
});

test('registration can set status', function () {
    $registration = Registration::factory()->create(['status' => 'pending']);

    $registration->setStatus('active', 'Activated');

    expect($registration->fresh()->status)->toBe('active');
});

test('registration returns registration state', function () {
    $registration = Registration::factory()->create(['status' => 'active']);

    $state = $registration->asRegistrationState();

    expect($state)->toBeInstanceOf(RegistrationState::class);
});
