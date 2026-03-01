<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Academic\Models\Concerns\HasAcademicYear;

/**
 * Mock Model for Trait Testing
 */
class AcademicTestModel extends Model
{
    use HasAcademicYear;

    protected $table = 'academic_test_models';

    protected $fillable = ['name', 'academic_year'];
}

beforeEach(function () {
    static $created = false;
    if (! $created) {
        Schema::create('academic_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->uuid('academic_year')->index();
            $table->timestamps();
        });
        $created = true;
    }
    AcademicTestModel::query()->withoutGlobalScopes()->delete();
});

test('it automatically populates academic_year on creation from settings', function () {
    $yearA = '550e8400-e29b-41d4-a716-446655440000';
    setting(['active_academic_year' => $yearA]);

    $model = AcademicTestModel::create(['name' => 'Record in Year A']);

    expect($model->academic_year)->toBe($yearA);
});

test('it automatically scopes queries to the active academic year', function () {
    $yearA = '550e8400-e29b-41d4-a716-446655440000';
    $yearB = '550e8400-e29b-41d4-a716-446655440001';

    // Create record in Year B manually (bypassing scope for setup)
    AcademicTestModel::query()
        ->withoutGlobalScopes()
        ->insert([
            'name' => 'Hidden Record',
            'academic_year' => $yearB,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    // Set active to Year A
    setting(['active_academic_year' => $yearA]);
    AcademicTestModel::create(['name' => 'Visible Record']);

    // Querying should only return Year A record
    $results = AcademicTestModel::all();

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Visible Record');
    expect($results->first()->academic_year)->toBe($yearA);
});

test('it can bypass academic year scope when explicitly requested', function () {
    $yearA = '550e8400-e29b-41d4-a716-446655440000';
    $yearB = '550e8400-e29b-41d4-a716-446655440001';

    setting(['active_academic_year' => $yearA]);
    AcademicTestModel::create(['name' => 'Year A']);

    setting(['active_academic_year' => $yearB]);
    AcademicTestModel::create(['name' => 'Year B']);

    // Standard query (now in Year B)
    expect(AcademicTestModel::all())->toHaveCount(1);

    // Bypassing scope
    $allRecords = AcademicTestModel::query()->withoutGlobalScope('academic_year')->get();
    expect($allRecords)->toHaveCount(2);
});
