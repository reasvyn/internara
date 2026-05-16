<?php

declare(strict_types=1);

use App\Actions\School\DeleteAcademicYearAction;
use App\Exceptions\RejectedException;
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
    it('deletes an inactive academic year', function () {
        $year = AcademicYearFactory::new()->create(['is_active' => false]);

        app(DeleteAcademicYearAction::class)->execute($year);

        expect($year->fresh())->toBeNull();
    });

    it('throws when deleting an active academic year', function () {
        $year = AcademicYearFactory::new()->active()->create();

        expect(fn () => app(DeleteAcademicYearAction::class)->execute($year))
            ->toThrow(RejectedException::class);
    });
});
