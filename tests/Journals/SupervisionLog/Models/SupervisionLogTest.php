<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('supervision log belongs to registration', function () {
    $registration = Registration::factory()->create();
    $log = SupervisionLog::factory()->create(['registration_id' => $registration->id]);

    expect($log->registration)->toBeInstanceOf(Registration::class);
});

test('supervision log belongs to supervisor', function () {
    $supervisor = User::factory()->create();
    $log = SupervisionLog::factory()->create(['supervisor_id' => $supervisor->id]);

    expect($log->supervisor)->toBeInstanceOf(User::class);
});

test('default status is draft', function () {
    $log = SupervisionLog::factory()->create();

    expect($log->status->value)->toBe('draft');
});
