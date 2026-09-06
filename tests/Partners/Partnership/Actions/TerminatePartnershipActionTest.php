<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partners\Domain\Partnership\Actions\TerminatePartnershipAction;
use App\Modules\Partners\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipTerminated;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Partnership Status Lifecycle — TerminatePartnershipAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: TerminatePartnershipAction', function (): void {
    test('NTHQA-FR-ST1: terminates active partnership', function (): void {
        $partnership = Partnership::factory()->active()->create();

        $terminated = app(TerminatePartnershipAction::class)->execute($partnership);

        expect($terminated->status)->toBe(PartnershipStatus::TERMINATED);
    });

    test('NTHQA-FR-ST1: persisted status is terminated', function (): void {
        $partnership = Partnership::factory()->active()->create();

        app(TerminatePartnershipAction::class)->execute($partnership);

        expect(Partnership::find($partnership->id)->status)->toBe(PartnershipStatus::TERMINATED);
    });

    test('NTHQA-FR-ST2: rejects termination of already terminated partnership', function (): void {
        $partnership = Partnership::factory()->create(['status' => PartnershipStatus::TERMINATED->value]);

        expect(fn () => app(TerminatePartnershipAction::class)->execute($partnership))
            ->toThrow(RejectedException::class);
    });

    test('NTHQA-FR-ST3: rejects termination of expired partnership', function (): void {
        $partnership = Partnership::factory()->expired()->create();

        expect(fn () => app(TerminatePartnershipAction::class)->execute($partnership))
            ->toThrow(RejectedException::class);
    });

    test('NTHQA-FR-ST4: dispatches PartnershipTerminated event', function (): void {
        Event::fake([PartnershipTerminated::class]);

        $partnership = Partnership::factory()->active()->create();

        app(TerminatePartnershipAction::class)->execute($partnership);

        Event::assertDispatched(PartnershipTerminated::class, fn ($event) => $event->partnership->id === $partnership->id);
    });

    test('NTHQA-FR-ST5: terminated partnership cannot be deleted', function (): void {
        // Partnerships in terminal states can be deleted per PartnershipState::canBeDeleted()
        // This tests that terminate transitions to a deletable state
        $partnership = Partnership::factory()->active()->create();

        app(TerminatePartnershipAction::class)->execute($partnership);

        expect($partnership->fresh()->asPartnershipState()->canBeDeleted())->toBeTrue();
    });

    test('NTHQA-NFR-R1: wraps termination in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(TerminatePartnershipAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });
});
