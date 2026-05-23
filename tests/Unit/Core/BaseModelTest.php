<?php

declare(strict_types=1);

use App\Domain\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BMTestBaseModel extends BaseModel
{
    protected $table = 'bm_test_models';
}

describe('BaseModel', function () {
    it('is abstract', function () {
        $ref = new ReflectionClass(BaseModel::class);

        expect($ref->isAbstract())->toBeTrue();
    });

    it('uses HasUuids trait', function () {
        $traits = class_uses_recursive(BaseModel::class);

        expect($traits)->toContain(HasUuids::class);
    });

    it('is non-incrementing', function () {
        $model = new BMTestBaseModel;

        expect($model->getIncrementing())->toBeFalse();
    });

    it('has string key type', function () {
        $model = new BMTestBaseModel;

        expect($model->getKeyType())->toBe('string');
    });

    it('extends Eloquent Model', function () {
        $model = new BMTestBaseModel;

        expect($model)->toBeInstanceOf(Model::class);
    });

    it('has timestamps enabled by default', function () {
        $model = new BMTestBaseModel;

        expect($model->timestamps)->toBeTrue();
    });

    it('generates UUID on new instance', function () {
        $model = new BMTestBaseModel;
        $model->id = Str::uuid()->toString();

        expect($model->id)->not->toBeNull()
            ->and(strlen($model->id))->toBe(36);
    });
});
