<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Academic\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
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
    });

    afterEach(function () {
        Carbon::setTestNow();
    });

    test('it applies global scope to filter by active academic year', function () {
        setting(['active_academic_year' => '2025/2026']);

        // Manually insert records without triggering trait
        AcademicYearTestModel::query()->withoutGlobalScopes()->insert([
            ['name' => 'Visible', 'academic_year' => '2025/2026', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hidden', 'academic_year' => '2024/2025', 'created_at' => now(), 'updated_at' => now()],
        ]);

        expect(AcademicYearTestModel::count())
            ->toBe(1)
            ->and(AcademicYearTestModel::first()->name)
            ->toBe('Visible');
    });

    test('it allows manual academic year scoping', function () {
        // Manually insert test data
        AcademicYearTestModel::query()->withoutGlobalScopes()->insert([
            ['name' => 'Old Item', 'academic_year' => '2024/2025', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'New Item', 'academic_year' => '2025/2026', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = AcademicYearTestModel::forAcademicYear('2024/2025')->get();

        expect($results)
            ->toHaveCount(1)
            ->and($results->first()->name)
            ->toBe('Old Item');
    });

    test('it can bypass global scope', function () {
        // Manually insert test data
        AcademicYearTestModel::query()->withoutGlobalScopes()->insert([
            ['name' => 'Year A', 'academic_year' => '2025/2026', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Year B', 'academic_year' => '2024/2025', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $allRecords = AcademicYearTestModel::query()
            ->withoutGlobalScope('academic_year')
            ->get();

        expect($allRecords)->toHaveCount(2);
    });

    test('it respects manually set academic_year on creation via query', function () {
        setting(['active_academic_year' => '2025/2026']);

        $model = AcademicYearTestModel::query()->withoutGlobalScopes()->create([
            'name' => 'Manual Year',
            'academic_year' => '2020/2021',
        ]);

        expect($model->academic_year)->toBe('2020/2021');
    });
});