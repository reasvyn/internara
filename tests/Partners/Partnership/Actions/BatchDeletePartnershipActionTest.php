<?php

declare(strict_types=1);

use App\Modules\Partners\Domain\Partnership\Actions\BatchDeletePartnershipAction;
use App\Modules\Partners\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Partnership Batch Delete — BatchDeletePartnershipAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: BatchDeletePartnershipAction', function (): void {
    test('NTHQA-FR-PC13: deletes multiple deletable partnerships', function (): void {
        $expired1 = Partnership::factory()->expired()->create();
        $expired2 = Partnership::factory()->expired()->create();

        $result = app(BatchDeletePartnershipAction::class)->execute([$expired1->id, $expired2->id]);

        expect($result)->toBe(['deleted' => 2, 'blocked' => 0])
            ->and(Partnership::find($expired1->id))->toBeNull()
            ->and(Partnership::find($expired2->id))->toBeNull();
    });

    test('NTHQA-FR-PC14: blocks active partnerships in batch', function (): void {
        $active = Partnership::factory()->active()->create();
        $expired = Partnership::factory()->expired()->create();

        $result = app(BatchDeletePartnershipAction::class)->execute([$active->id, $expired->id]);

        expect($result)->toBe(['deleted' => 1, 'blocked' => 1])
            ->and(Partnership::find($active->id))->not->toBeNull()
            ->and(Partnership::find($expired->id))->toBeNull();
    });

    test('NTHQA-FR-PC13: skips non-existent IDs gracefully', function (): void {
        $expired = Partnership::factory()->expired()->create();

        $result = app(BatchDeletePartnershipAction::class)->execute([$expired->id, 'non-existent-id']);

        expect($result)->toBe(['deleted' => 1, 'blocked' => 0]);
    });

    test('NTHQA-FR-PC13: returns zero counts for empty array', function (): void {
        $result = app(BatchDeletePartnershipAction::class)->execute([]);

        expect($result)->toBe(['deleted' => 0, 'blocked' => 0]);
    });

    test('NTHQA-FR-ST10: mixed batch with terminated and active', function (): void {
        $terminated = Partnership::factory()->create(['status' => PartnershipStatus::TERMINATED->value]);
        $active1 = Partnership::factory()->active()->create();
        $active2 = Partnership::factory()->active()->create();

        $result = app(BatchDeletePartnershipAction::class)->execute([
            $terminated->id,
            $active1->id,
            $active2->id,
        ]);

        expect($result)->toBe(['deleted' => 1, 'blocked' => 2]);
    });
});
