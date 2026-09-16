<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Entities\RegistrationState;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationDocumentStatus;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Policies\RegistrationPolicy;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('MBB5R-FR-REG-003: active state answers active and not pending', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['status' => 'active']));

    expect($state->isActive())->toBeTrue()->and($state->isPending())->toBeFalse();
});

test('MBB5R-FR-REG-003: pending state answers pending and not active', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['status' => 'pending']));

    expect($state->isPending())->toBeTrue()->and($state->isActive())->toBeFalse();
});

test('MBB5R-FR-REG-004: pending registration with placement is approvable', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['status' => 'pending', 'placement_id' => 'p']));

    expect($state->canBeApproved())->toBeTrue();
});

test('MBB5R-FR-REG-004: active registration cannot be approved again', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['status' => 'active', 'placement_id' => 'p']));

    expect($state->canBeApproved())->toBeFalse();
});

test('MBB5R-FR-REG-004: pending registration without placement is not approvable', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['status' => 'pending', 'placement_id' => null]));

    expect($state->canBeApproved())->toBeFalse();
});

test('MBB5R-FR-REG-005: state reports days remaining and total duration', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make([
        'start_date' => '2026-01-01', 'end_date' => '2026-01-31',
    ]));

    expect($state->daysRemaining(Carbon::parse('2026-01-15')))->toBe(16)
        ->and($state->totalDuration())->toBe(30);
});

test('MBB5R-FR-REG-005: expired state clamps days remaining at zero', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['end_date' => '2026-01-01']));

    expect($state->daysRemaining(Carbon::parse('2026-01-02')))->toBe(0);
});

test('MBB5R-FR-REG-005: missing dates produce zero duration', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['start_date' => null, 'end_date' => null]));

    expect($state->daysRemaining(Carbon::parse('2026-01-01')))->toBe(0)->and($state->totalDuration())->toBe(0);
});

test('MBB5R-FR-REG-006: current phase follows elapsed weighted progress', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make([
        'start_date' => '2026-01-01', 'end_date' => '2026-01-11',
    ]))->withPhases([
        ['name' => 'Preparation', 'order' => 1, 'weight' => 50],
        ['name' => 'Practice', 'order' => 2, 'weight' => 50],
    ]);

    expect($state->currentPhaseIndex(Carbon::parse('2026-01-06')))->toBe(0)
        ->and($state->currentPhase(Carbon::parse('2026-01-06')))->toBe('Preparation');
});

test('MBB5R-FR-REG-006: phase data is immutable when adding phases', function (): void {
    $original = RegistrationState::fromModel(Registration::factory()->make());
    $changed = $original->withPhases([['name' => 'Practice', 'order' => 1, 'weight' => 100]]);

    expect($original->phases())->toBeEmpty()->and($changed->phases())->toHaveCount(1);
});

test('MBB5R-FR-REG-006: no phase is returned when dates are unavailable', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['start_date' => null, 'end_date' => null]))
        ->withPhases([['name' => 'Only', 'order' => 1, 'weight' => 100]]);

    expect($state->currentPhaseIndex())->toBeNull()->and($state->currentPhase())->toBeNull();
});

test('MBB5R-FR-REG-021: document status vocabulary has three values', function (): void {
    expect(RegistrationDocumentStatus::cases())->toHaveCount(3)
        ->and(array_column(RegistrationDocumentStatus::cases(), 'value'))->toEqual(['pending', 'verified', 'rejected']);
});

test('MBB5R-FR-REG-022: pending documents transition to both outcomes', function (): void {
    expect(RegistrationDocumentStatus::PENDING->canTransitionTo(RegistrationDocumentStatus::VERIFIED))->toBeTrue()
        ->and(RegistrationDocumentStatus::PENDING->canTransitionTo(RegistrationDocumentStatus::REJECTED))->toBeTrue();
});

test('MBB5R-FR-REG-026: terminal document states have no outgoing transitions', function (): void {
    foreach ([RegistrationDocumentStatus::VERIFIED, RegistrationDocumentStatus::REJECTED] as $terminal) {
        expect($terminal->canTransitionTo(RegistrationDocumentStatus::PENDING))->toBeFalse()
            ->and($terminal->canTransitionTo($terminal))->toBeFalse();
    }
});

test('MBB5R-FR-REG-018: student may update their pending registration', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');
    $registration = Registration::factory()->make(['student_id' => $student->id, 'status' => 'pending']);

    expect((new RegistrationPolicy)->update($student, $registration))->toBeTrue();
});

test('MBB5R-FR-REG-018: student may not update their active registration', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');
    $registration = Registration::factory()->make(['student_id' => $student->id, 'status' => 'active']);

    expect((new RegistrationPolicy)->update($student, $registration))->toBeFalse();
});

test('MBB5R-FR-REG-019: admin may approve registrations', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect((new RegistrationPolicy)->approve($admin, Registration::factory()->make()))->toBeTrue();
});

test('MBB5R-FR-REG-019: student may not approve registrations', function (): void {
    $student = User::factory()->create();
    $student->assignRole('student');

    expect((new RegistrationPolicy)->approve($student, Registration::factory()->make()))->toBeFalse();
});

test('MBB5R-FR-REG-003: ongoing state includes both date boundaries', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make([
        'start_date' => '2026-01-01', 'end_date' => '2026-01-31',
    ]));

    expect($state->isCurrentlyOngoing(Carbon::parse('2026-01-01')))->toBeTrue()
        ->and($state->isCurrentlyOngoing(Carbon::parse('2026-01-31')))->toBeTrue();
});

test('MBB5R-FR-REG-003: ended state begins after the end date', function (): void {
    $state = RegistrationState::fromModel(Registration::factory()->make(['end_date' => '2026-01-31']));

    expect($state->hasEnded(Carbon::parse('2026-02-01')))->toBeTrue()
        ->and($state->hasEnded(Carbon::parse('2026-01-31')))->toBeFalse();
});
