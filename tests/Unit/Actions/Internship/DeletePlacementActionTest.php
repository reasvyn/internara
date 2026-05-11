<?php

declare(strict_types=1);

use App\Actions\Internship\DeletePlacementAction;
use Database\Factories\PlacementFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes a placement', function () {
        $placement = PlacementFactory::new()->create();

        app(DeletePlacementAction::class)->execute($placement);

        expect($placement->fresh())->toBeNull();
    });
});
