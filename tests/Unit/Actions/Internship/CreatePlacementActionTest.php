<?php

declare(strict_types=1);

use App\Actions\Internship\CreatePlacementAction;
use App\Models\Placement;
use Database\Factories\CompanyFactory;
use Database\Factories\InternshipFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a placement with filled quota zero', function () {
        $internship = InternshipFactory::new()->create();
        $company = CompanyFactory::new()->create();

        $placement = app(CreatePlacementAction::class)->execute([
            'name' => 'Software Dev Placement',
            'internship_id' => $internship->id,
            'company_id' => $company->id,
            'quota' => 5,
        ]);

        expect($placement)->toBeInstanceOf(Placement::class)
            ->and($placement->name)->toBe('Software Dev Placement')
            ->and($placement->filled_quota)->toBe(0);
    });
});
