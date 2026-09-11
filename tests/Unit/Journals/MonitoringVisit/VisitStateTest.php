<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\MonitoringVisit\Entities\VisitState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class VisitStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('2EHSE: visit state', function (): void {
    test('2EHSE-FR-SUPV-010: fromModel bridges verification and date without persisting', function (): void {
        $model = new VisitStateModelDouble([
            'is_verified' => false,
            'visit_date' => Carbon::now()->subDays(3),
        ]);

        $state = VisitState::fromModel($model);

        expect($state->canBeEdited())->toBeTrue();
        expect($state->canBeDeleted())->toBeTrue();
        expect($state->isRecent(Carbon::now()))->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('2EHSE-FR-SUPV-009: fromModel reports a verified visit as locked', function (): void {
        $model = new VisitStateModelDouble([
            'is_verified' => true,
            'visit_date' => Carbon::now()->subDays(20),
        ]);

        $state = VisitState::fromModel($model);

        expect($state->canBeEdited())->toBeFalse();
        expect($state->canBeDeleted())->toBeFalse();
        expect($state->isRecent(Carbon::now()))->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('2EHSE-FR-SUPV-010: editable and deletable follow the verified flag', function (): void {
        $open = VisitState::fromArray(['isVerified' => false, 'visitDate' => null]);
        $locked = VisitState::fromArray(['isVerified' => true, 'visitDate' => null]);

        expect($open->canBeEdited())->toBeTrue();
        expect($open->canBeDeleted())->toBeTrue();
        expect($locked->canBeEdited())->toBeFalse();
        expect($locked->canBeDeleted())->toBeFalse();
    });

    test('2EHSE-FR-SUPV-010: isRecent covers the seven-day freshness window', function (): void {
        $now = Carbon::parse('2026-05-10 12:00:00');
        $recent = VisitState::fromArray(['isVerified' => false, 'visitDate' => Carbon::parse('2026-05-04 12:00:00')]);
        $stale = VisitState::fromArray(['isVerified' => false, 'visitDate' => Carbon::parse('2026-04-20 12:00:00')]);
        $dateless = VisitState::fromArray(['isVerified' => false, 'visitDate' => null]);

        expect($recent->isRecent($now))->toBeTrue();
        expect($stale->isRecent($now))->toBeFalse();
        expect($dateless->isRecent($now))->toBeFalse();
    });

    test('2EHSE-FR-SUPV-010: fromArray rejects a missing verified flag', function (): void {
        expect(fn (): VisitState => VisitState::fromArray(['visitDate' => null]))
            ->toThrow(InvalidArgumentException::class, 'isVerified');
    });
});
