<?php

declare(strict_types=1);

use App\Actions\School\CreateAcademicYearAction;
use App\Models\AcademicYear;
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
    it('creates an academic year', function () {
        $year = app(CreateAcademicYearAction::class)->execute([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        expect($year)->toBeInstanceOf(AcademicYear::class)
            ->and($year->name)->toBe('2025/2026')
            ->and($year->is_active)->toBeFalse();
    });

    it('creates active academic year when specified', function () {
        $year = app(CreateAcademicYearAction::class)->execute([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        expect($year->is_active)->toBeTrue();
    });
});
