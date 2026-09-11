<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Entities\RegistrationState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class RegistrationStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('MBB5R: registration state', function (): void {
    $window = fn (): array => [
        'status' => 'active',
        'startDate' => Carbon::parse('2026-01-01 00:00:00'),
        'endDate' => Carbon::parse('2026-01-11 00:00:00'),
        'hasPlacement' => true,
    ];

    test('MBB5R-FR-REG-003: fromModel bridges status, dates, and placement without persisting', function (): void {
        $model = new RegistrationStateModelDouble([
            'status' => 'pending',
            'start_date' => Carbon::parse('2026-01-01 00:00:00'),
            'end_date' => Carbon::parse('2026-01-11 00:00:00'),
            'placement_id' => 'placement-1',
        ]);

        $state = RegistrationState::fromModel($model);

        expect($state->isPending())->toBeTrue();
        expect($state->isActive())->toBeFalse();
        expect($state->canBeApproved())->toBeTrue();
        expect($state->totalDuration())->toBe(10);
        expect($model->exists)->toBeFalse();
    });

    test('MBB5R-FR-REG-012: isActive and isPending answer the stored status strings', function () use ($window): void {
        expect(RegistrationState::fromArray([...$window(), 'status' => 'active'])->isActive())->toBeTrue();
        expect(RegistrationState::fromArray([...$window(), 'status' => 'pending'])->isActive())->toBeFalse();
        expect(RegistrationState::fromArray([...$window(), 'status' => 'pending'])->isPending())->toBeTrue();
        expect(RegistrationState::fromArray([...$window(), 'status' => 'active'])->isPending())->toBeFalse();
    });

    test('MBB5R-FR-REG-003: isCurrentlyOngoing covers the inclusive window', function () use ($window): void {
        $state = RegistrationState::fromArray($window());

        expect($state->isCurrentlyOngoing(Carbon::parse('2026-01-01 00:00:00')))->toBeTrue();
        expect($state->isCurrentlyOngoing(Carbon::parse('2026-01-05 12:00:00')))->toBeTrue();
        expect($state->isCurrentlyOngoing(Carbon::parse('2026-01-11 00:00:00')))->toBeTrue();
        expect($state->isCurrentlyOngoing(Carbon::parse('2025-12-31 23:59:59')))->toBeFalse();
        expect($state->isCurrentlyOngoing(Carbon::parse('2026-01-12 00:00:00')))->toBeFalse();
    });

    test('MBB5R-FR-REG-003: ongoing and ended are false without both dates', function (): void {
        $dateless = RegistrationState::fromArray(['status' => 'active', 'startDate' => null, 'endDate' => null, 'hasPlacement' => false]);

        expect($dateless->isCurrentlyOngoing(Carbon::parse('2026-01-05 00:00:00')))->toBeFalse();
        expect($dateless->hasEnded(Carbon::parse('2026-01-05 00:00:00')))->toBeFalse();
        expect($dateless->daysRemaining(Carbon::parse('2026-01-05 00:00:00')))->toBe(0);
        expect($dateless->totalDuration())->toBe(0);
    });

    test('MBB5R-FR-REG-003: hasEnded trips only past the end date', function () use ($window): void {
        $state = RegistrationState::fromArray($window());

        expect($state->hasEnded(Carbon::parse('2026-01-11 00:00:00')))->toBeFalse();
        expect($state->hasEnded(Carbon::parse('2026-01-12 00:00:00')))->toBeTrue();
    });

    test('MBB5R-FR-REG-004: canBeApproved needs pending status together with a placement', function () use ($window): void {
        expect(RegistrationState::fromArray([...$window(), 'status' => 'pending', 'hasPlacement' => true])->canBeApproved())->toBeTrue();
        expect(RegistrationState::fromArray([...$window(), 'status' => 'pending', 'hasPlacement' => false])->canBeApproved())->toBeFalse();
        expect(RegistrationState::fromArray([...$window(), 'status' => 'active', 'hasPlacement' => true])->canBeApproved())->toBeFalse();
    });

    test('MBB5R-FR-REG-005: daysRemaining counts down to zero and totalDuration spans the window', function () use ($window): void {
        $state = RegistrationState::fromArray($window());

        expect($state->daysRemaining(Carbon::parse('2026-01-01 00:00:00')))->toBe(10);
        expect($state->daysRemaining(Carbon::parse('2026-01-11 00:00:00')))->toBe(0);
        expect($state->daysRemaining(Carbon::parse('2026-02-01 00:00:00')))->toBe(0);
        expect($state->totalDuration())->toBe(10);
    });

    test('MBB5R-FR-REG-006: currentPhase follows the elapsed share of the duration', function () use ($window): void {
        $phases = [
            ['name' => 'Orientation', 'order' => 1, 'weight' => 50],
            ['name' => 'Placement', 'order' => 2, 'weight' => 50],
        ];
        $state = RegistrationState::fromArray($window())->withPhases($phases);

        expect($state->phases())->toBe($phases);
        expect($state->currentPhaseIndex(Carbon::parse('2026-01-02 00:00:00')))->toBe(0);
        expect($state->currentPhase(Carbon::parse('2026-01-02 00:00:00')))->toBe('Orientation');
        expect($state->currentPhaseIndex(Carbon::parse('2026-01-08 00:00:00')))->toBe(1);
        expect($state->currentPhase(Carbon::parse('2026-01-08 00:00:00')))->toBe('Placement');
    });

    test('MBB5R-FR-REG-006: currentPhase is null without phases or dates', function () use ($window): void {
        expect(RegistrationState::fromArray($window())->currentPhaseIndex(Carbon::parse('2026-01-05 00:00:00')))->toBeNull();
        expect(RegistrationState::fromArray($window())->currentPhase(Carbon::parse('2026-01-05 00:00:00')))->toBeNull();
    });

    test('MBB5R-FR-REG-003: fromArray rejects a missing status', function (): void {
        expect(fn (): RegistrationState => RegistrationState::fromArray([
            'startDate' => null, 'endDate' => null, 'hasPlacement' => false,
        ]))->toThrow(InvalidArgumentException::class, 'status');
    });

    test('MBB5R-FR-REG-003: equals compares snapshots by value', function () use ($window): void {
        $state = RegistrationState::fromArray($window());

        expect($state->equals(RegistrationState::fromArray($window())))->toBeTrue();
        expect($state->equals(RegistrationState::fromArray([...$window(), 'status' => 'pending'])))->toBeFalse();
    });
});
