<?php

declare(strict_types=1);

use App\Actions\Internship\UpdatePlacementAction;
use Database\Factories\PlacementFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates a placement', function () {
        $placement = PlacementFactory::new()->create();

        $result = app(UpdatePlacementAction::class)->execute($placement, [
            'name' => 'Updated Placement',
        ]);

        expect($result->name)->toBe('Updated Placement');
    });
});
