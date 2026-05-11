<?php

declare(strict_types=1);

use App\Actions\Internship\DirectPlacementAction;
use Database\Factories\InternshipFactory;
use Database\Factories\PlacementFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a direct placement registration', function () {
        $student = UserFactory::new()->create();
        $internship = InternshipFactory::new()->create();
        $placement = PlacementFactory::new()->create([
            'internship_id' => $internship->id,
            'quota' => 10,
        ]);

        $registration = app(DirectPlacementAction::class)->execute($student, [
            'placement_id' => $placement->id,
            'academic_year' => '2025/2026',
        ]);

        expect($registration->placement_id)->toBe($placement->id)
            ->and($placement->fresh()->filled_quota)->toBe(1);
    });
});
