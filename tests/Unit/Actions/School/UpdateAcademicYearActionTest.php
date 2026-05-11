<?php

declare(strict_types=1);

use App\Actions\School\UpdateAcademicYearAction;
use App\Models\AcademicYear;
use Database\Factories\AcademicYearFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeAll(function () {
    require_once getcwd().'/app/Models/AcademicYear.php';
    class_alias(
        AcademicYear::class,
        App\Models\School\AcademicYear::class,
    );
});

describe('execute', function () {
    it('updates an academic year', function () {
        $year = AcademicYearFactory::new()->create();

        $result = app(UpdateAcademicYearAction::class)->execute($year, [
            'name' => '2027/2028',
        ]);

        expect($result->name)->toBe('2027/2028');
    });
});
