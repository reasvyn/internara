<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Models\Concerns\HasStatus;

uses(RefreshDatabase::class);

class StatusTestModel extends Model
{
    use HasStatus;

    protected $table = 'status_test_models';

    protected $fillable = ['name'];
}

beforeEach(function () {
    // Migration for statuses table is required by Spatie Model Status
    // It should be handled by RefreshDatabase if the migration exists in modules/Core/database/migrations
    // as I saw earlier.
    Schema::create('status_test_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('status_test_models');
});

test('HasStatus trait can set and get status', function () {
    $model = StatusTestModel::create(['name' => 'Test']);

    $model->setStatus('active');

    $model = $model->fresh();

    expect($model->statuses)
        ->toHaveCount(1)
        ->and($model->latestStatus()->name)
        ->toBe('active')
        ->and($model->getStatusColor())
        ->toBe('success');
});

test('getStatusLabel returns translated label', function () {
    app()->setLocale('en');
    $model = StatusTestModel::create(['name' => 'Test']);
    $model->setStatus('active');

    expect($model->getStatusLabel())->toBe('Active');
});
