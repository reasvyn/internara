<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Unit\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatuses;

uses(RefreshDatabase::class);

class StatusTestModel extends Model
{
    use HasStatuses, HasUuid;

    protected $table = 'status_test_models';

    protected $fillable = ['name'];
}

beforeEach(function () {
    Schema::create('status_test_models', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->timestamps();
    });
});

test('has status trait can set and get status', function () {
    $model = StatusTestModel::create(['name' => 'Test']);

    $model->setStatus('verified');

    $model = $model->fresh();

    expect($model->statuses)
        ->toHaveCount(1)
        ->and($model->latestStatus()->name)
        ->toBe('verified')
        ->and($model->getStatusColor())
        ->toBe('#10b981');
});

test('get status label returns translated label', function () {
    app()->setLocale('en');
    $model = StatusTestModel::create(['name' => 'Test']);
    $model->setStatus('verified');

    expect($model->getStatusLabel())->toBe('Verified');
});
