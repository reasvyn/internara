<?php

declare(strict_types=1);

use App\Actions\School\ActivateAcademicYearAction;
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
    it('activates a year and deactivates others', function () {
        $year1 = AcademicYearFactory::new()->active()->create(['name' => '2023/2024']);
        $year2 = AcademicYearFactory::new()->create(['name' => '2024/2025', 'is_active' => false]);

        app(ActivateAcademicYearAction::class)->execute($year2);

        expect($year1->fresh()->is_active)->toBeFalse()
            ->and($year2->fresh()->is_active)->toBeTrue();
    });
});
