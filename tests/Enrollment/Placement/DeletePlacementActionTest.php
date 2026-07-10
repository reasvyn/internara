<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement\Actions\DeletePlacementAction;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes placement with no registrations', function () {
    $placement = Placement::factory()->create();

    app(DeletePlacementAction::class)->execute($placement);

    $this->assertModelMissing($placement);
});

test('throws exception when placement has active registrations', function () {
    $internship = Internship::factory()->create();
    $placement = Placement::factory()->create(['internship_id' => $internship->id]);
    Registration::factory()->create(['internship_id' => $internship->id, 'placement_id' => $placement->id, 'status' => 'active']);

    expect(fn () => app(DeletePlacementAction::class)->execute($placement->fresh()))
        ->toThrow(RejectedException::class);
});
