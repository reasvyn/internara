<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partners\Domain\Partnership\Actions\DeletePartnershipAction;
use App\Modules\Partners\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipDeleted;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Partnership Management — DeletePartnershipAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: DeletePartnershipAction', function (): void {
    test('NTHQA-FR-PC9: deletes expired partnership', function (): void {
        $partnership = Partnership::factory()->expired()->create();

        app(DeletePartnershipAction::class)->execute($partnership);

        expect(Partnership::find($partnership->id))->toBeNull();
    });

    test('NTHQA-FR-PC9: deletes terminated partnership', function (): void {
        $partnership = Partnership::factory()->create(['status' => PartnershipStatus::TERMINATED->value]);

        app(DeletePartnershipAction::class)->execute($partnership);

        expect(Partnership::find($partnership->id))->toBeNull();
    });

    test('NTHQA-FR-PC10: rejects deletion of active partnership', function (): void {
        $partnership = Partnership::factory()->active()->create();

        expect(fn () => app(DeletePartnershipAction::class)->execute($partnership))
            ->toThrow(RejectedException::class);
    });

    test('NTHQA-FR-PC11: dispatches PartnershipDeleted event', function (): void {
        Event::fake([PartnershipDeleted::class]);

        $partnership = Partnership::factory()->expired()->create();

        app(DeletePartnershipAction::class)->execute($partnership);

        Event::assertDispatched(PartnershipDeleted::class, fn ($event) => $event->partnership->id === $partnership->id);
    });

    test('NTHQA-FR-PC12: active partnership still exists after failed delete attempt', function (): void {
        $partnership = Partnership::factory()->active()->create();

        try {
            app(DeletePartnershipAction::class)->execute($partnership);
        } catch (RejectedException) {
            // Expected
        }

        expect(Partnership::find($partnership->id))->not->toBeNull()
            ->and(Partnership::find($partnership->id)->status)->toBe(PartnershipStatus::ACTIVE);
    });

    test('NTHQA-NFR-R1: wraps deletion in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(DeletePartnershipAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });
});
