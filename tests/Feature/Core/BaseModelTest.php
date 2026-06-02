<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

class TestModel extends BaseModel
{
    protected $table = 'test_models';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('test_models', function ($table) {
        $table->uuid('id')->primary();
        $table->timestamps();
    });
});

describe('BaseModel', function () {
    it('is abstract', function () {
        expect((new \ReflectionClass(BaseModel::class))->isAbstract())->toBeTrue();
    });

    it('uses HasUuids trait', function () {
        expect(in_array(HasUuids::class, class_uses_recursive(BaseModel::class)))->toBeTrue();
    });

    it('has non-incrementing IDs', function () {
        $model = new TestModel;

        expect($model->getIncrementing())->toBeFalse();
    });

    it('uses string key type', function () {
        $model = new TestModel;

        expect($model->getKeyType())->toBe('string');
    });

    it('generates UUID on create', function () {
        $model = new TestModel;
        $model->save();

        expect($model->id)->toBeString()
            ->and(strlen($model->id))->toBe(36)
            ->and($model->id)->toMatch('/^[0-9a-f-]+$/');
    });
});
