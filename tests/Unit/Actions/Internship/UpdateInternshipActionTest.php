<?php

declare(strict_types=1);

use App\Actions\Internship\UpdateInternshipAction;
use Database\Factories\InternshipFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates an internship', function () {
        $internship = InternshipFactory::new()->create();

        $result = app(UpdateInternshipAction::class)->execute($internship, [
            'name' => 'Updated Internship',
        ]);

        expect($result->name)->toBe('Updated Internship');
    });
});
