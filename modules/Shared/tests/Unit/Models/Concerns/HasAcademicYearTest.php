<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Models\Concerns\HasAcademicYear;

uses(RefreshDatabase::class);

class AcademicYearTestModel extends Model
{
    use HasAcademicYear;

    protected $table = 'academic_year_test_models';

    protected $fillable = ['name', 'academic_year'];
}

beforeEach(function () {
    Schema::create('academic_year_test_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('academic_year');
        $table->timestamps();
    });

    \Modules\Setting\Facades\Setting::shouldReceive('getValue')
        ->with('active_academic_year', \Mockery::any(), \Mockery::any())
        ->andReturn('2025/2026');
});

afterEach(function () {
    Schema::dropIfExists('academic_year_test_models');
});

test('HasAcademicYear trait populates academic_year on creation', function () {
    // We need to ensure the setting() helper returns the value.
    // Since I don't see the setting() implementation yet, I'll mock it if it uses app()->make('settings')
    // or similar. For now, let's assume it's available.

    $model = AcademicYearTestModel::create(['name' => 'Test']);

    expect($model->academic_year)->toBe('2025/2026');
});

test('HasAcademicYear trait scopes queries by active academic year', function () {
    AcademicYearTestModel::create(['name' => 'Current', 'academic_year' => '2025/2026']);
    AcademicYearTestModel::create(['name' => 'Old', 'academic_year' => '2024/2025']);

    expect(AcademicYearTestModel::count())
        ->toBe(1)
        ->and(AcademicYearTestModel::first()->name)
        ->toBe('Current');
});

test('scopeForAcademicYear allows querying specific year', function () {
    AcademicYearTestModel::create(['name' => 'Current', 'academic_year' => '2025/2026']);
    AcademicYearTestModel::create(['name' => 'Old', 'academic_year' => '2024/2025']);

    $oldModels = AcademicYearTestModel::forAcademicYear('2024/2025')->get();

    expect($oldModels)
        ->toHaveCount(1)
        ->and($oldModels->first()->name)
        ->toBe('Old');
});
