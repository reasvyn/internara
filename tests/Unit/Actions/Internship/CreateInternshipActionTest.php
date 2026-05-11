<?php

declare(strict_types=1);

use App\Actions\Internship\CreateInternshipAction;
use App\Models\Internship;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates an internship from array data', function () {
        $internship = app(CreateInternshipAction::class)->execute([
            'name' => 'Summer Internship 2026',
            'description' => 'A great opportunity',
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-31',
        ]);

        expect($internship)->toBeInstanceOf(Internship::class)
            ->and($internship->name)->toBe('Summer Internship 2026');
    });
});
