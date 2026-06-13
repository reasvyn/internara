<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Journals\AbsenceRequest\Actions\ProcessAbsenceAction;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('approves absence request', function () {
    $processor = User::factory()->create();
    $absence = AbsenceRequest::factory()->create(['status' => AbsenceRequestStatus::PENDING]);

    $processed = app(ProcessAbsenceAction::class)->execute(
        $absence,
        $processor,
        AbsenceRequestStatus::APPROVED,
        notes: 'Approved, valid reason.',
    );

    expect($processed->status)->toBe(AbsenceRequestStatus::APPROVED);
    expect($processed->processed_by)->toBe($processor->id);
    expect($processed->processed_at)->not->toBeNull();
    expect($processed->admin_notes)->toBe('Approved, valid reason.');
});

test('rejects absence request', function () {
    $processor = User::factory()->create();
    $absence = AbsenceRequest::factory()->create(['status' => AbsenceRequestStatus::PENDING]);

    $processed = app(ProcessAbsenceAction::class)->execute(
        $absence,
        $processor,
        AbsenceRequestStatus::REJECTED,
        notes: 'Insufficient documentation.',
    );

    expect($processed->status)->toBe(AbsenceRequestStatus::REJECTED);
    expect($processed->processed_by)->toBe($processor->id);
    expect($processed->admin_notes)->toBe('Insufficient documentation.');
});

test('processes absence without admin notes', function () {
    $processor = User::factory()->create();
    $absence = AbsenceRequest::factory()->create(['status' => AbsenceRequestStatus::PENDING]);

    $processed = app(ProcessAbsenceAction::class)->execute(
        $absence,
        $processor,
        AbsenceRequestStatus::APPROVED,
    );

    expect($processed->status)->toBe(AbsenceRequestStatus::APPROVED);
    expect($processed->admin_notes)->toBeNull();
});

test('throws exception when processing already processed absence', function () {
    $processor = User::factory()->create();
    $absence = AbsenceRequest::factory()->create(['status' => AbsenceRequestStatus::APPROVED]);

    app(ProcessAbsenceAction::class)->execute(
        $absence,
        $processor,
        AbsenceRequestStatus::REJECTED,
    );
})->throws(RejectedException::class, 'This absence request has already been processed.');
