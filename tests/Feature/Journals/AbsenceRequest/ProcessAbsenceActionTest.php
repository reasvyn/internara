<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Journals\AbsenceRequest\Actions\ProcessAbsenceAction;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('approves absence request', function () {
    $processor = User::factory()->create();
    $attendance = Attendance::factory()->create(['absence_status' => AbsenceRequestStatus::PENDING->value]);

    $processed = app(ProcessAbsenceAction::class)->execute(
        $attendance,
        $processor,
        AbsenceRequestStatus::APPROVED,
        notes: 'Approved, valid reason.',
    );

    expect($processed->absence_status)->toBe(AbsenceRequestStatus::APPROVED);
    expect($processed->absence_processed_by)->toBe($processor->id);
    expect($processed->absence_processed_at)->not->toBeNull();
    expect($processed->absence_admin_notes)->toBe('Approved, valid reason.');
});

test('rejects absence request', function () {
    $processor = User::factory()->create();
    $attendance = Attendance::factory()->create(['absence_status' => AbsenceRequestStatus::PENDING->value]);

    $processed = app(ProcessAbsenceAction::class)->execute(
        $attendance,
        $processor,
        AbsenceRequestStatus::REJECTED,
        notes: 'Insufficient documentation.',
    );

    expect($processed->absence_status)->toBe(AbsenceRequestStatus::REJECTED);
    expect($processed->absence_processed_by)->toBe($processor->id);
    expect($processed->absence_admin_notes)->toBe('Insufficient documentation.');
});

test('processes absence without admin notes', function () {
    $processor = User::factory()->create();
    $attendance = Attendance::factory()->create(['absence_status' => AbsenceRequestStatus::PENDING->value]);

    $processed = app(ProcessAbsenceAction::class)->execute(
        $attendance,
        $processor,
        AbsenceRequestStatus::APPROVED,
    );

    expect($processed->absence_status)->toBe(AbsenceRequestStatus::APPROVED);
    expect($processed->absence_admin_notes)->toBeNull();
});

test('throws exception when processing already processed absence', function () {
    $processor = User::factory()->create();
    $attendance = Attendance::factory()->create(['absence_status' => AbsenceRequestStatus::APPROVED->value]);

    app(ProcessAbsenceAction::class)->execute(
        $attendance,
        $processor,
        AbsenceRequestStatus::REJECTED,
    );
})->throws(RejectedException::class, 'This absence request has already been processed.');
