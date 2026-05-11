<?php

declare(strict_types=1);

use App\Actions\Internship\DeleteInternshipAction;
use Database\Factories\InternshipFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes an internship', function () {
        $internship = InternshipFactory::new()->create();

        app(DeleteInternshipAction::class)->execute($internship);

        expect($internship->fresh())->toBeNull();
    });
});
