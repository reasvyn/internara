<?php

declare(strict_types=1);

use App\Actions\School\UpdateSchoolAction;
use Database\Factories\SchoolFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates a school', function () {
        $school = SchoolFactory::new()->create();

        $result = app(UpdateSchoolAction::class)->execute($school, [
            'name' => 'Updated School Name',
        ]);

        expect($result->name)->toBe('Updated School Name');
    });
});
