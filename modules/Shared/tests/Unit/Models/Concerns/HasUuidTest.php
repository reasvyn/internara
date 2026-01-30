<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Models\Concerns\HasUuid;

uses(RefreshDatabase::class);

class UuidTestModel extends Model
{
    use HasUuid;

    protected $table = 'uuid_test_models';

    protected $fillable = ['name'];
}

beforeEach(function () {
    Schema::create('uuid_test_models', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('uuid_test_models');
});

test('HasUuid trait generates uuid on creation', function () {
    $model = UuidTestModel::create(['name' => 'Test']);

    expect($model->id)
        ->not->toBeNull()
        ->and(strlen($model->id))
        ->toBe(36);
});
