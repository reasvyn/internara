<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Unit\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatus;



class StatusTestModel extends Model
{
    use HasStatus, HasUuid;

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

    $model->setStatus('active');

    $model = $model->fresh();

    expect($model->statuses)
        ->toHaveCount(1)
        ->and($model->latestStatus()->name)
        ->toBe('active')
        ->and($model->getStatusColor())
        ->toBe('success');
});

test('get status label returns translated label', function () {
    app()->setLocale('en');
    $model = StatusTestModel::create(['name' => 'Test']);
    $model->setStatus('active');

    expect($model->getStatusLabel())->toBe('Active');
});
