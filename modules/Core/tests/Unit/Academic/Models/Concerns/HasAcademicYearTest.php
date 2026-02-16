<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Academic\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Academic\Models\Concerns\HasAcademicYear;

uses(RefreshDatabase::class);

class AcademicYearTestModel extends Model
{
    use HasAcademicYear;

    protected $table = 'academic_year_test_models';

    protected $fillable = ['name', 'academic_year'];
}

describe('HasAcademicYear Trait', function () {
    beforeEach(function () {
        Schema::create('academic_year_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('academic_year');
            $table->timestamps();
        });

        // Mock setting helper/facade
        \Modules\Setting\Facades\Setting::shouldReceive('getValue')->andReturn('2025/2026');
    });

    test(
        'it fulfills [SYRS-F-101] by populating academic_year automatically on creation',
        function () {
            $model = AcademicYearTestModel::create(['name' => 'Test Item']);

            expect($model->academic_year)->toBe('2025/2026');
        },
    );

    test('it applies global scope to filter by active academic year', function () {
        AcademicYearTestModel::create(['name' => 'Visible', 'academic_year' => '2025/2026']);
        AcademicYearTestModel::create(['name' => 'Hidden', 'academic_year' => '2024/2025']);

        expect(AcademicYearTestModel::count())
            ->toBe(1)
            ->and(AcademicYearTestModel::first()->name)
            ->toBe('Visible');
    });

    test('it allows manual academic year scoping', function () {
        AcademicYearTestModel::create(['name' => 'Old Item', 'academic_year' => '2024/2025']);

        $results = AcademicYearTestModel::forAcademicYear('2024/2025')->get();

        expect($results)
            ->toHaveCount(1)
            ->and($results->first()->name)
            ->toBe('Old Item');
    });
});
