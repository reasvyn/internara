<?php

declare(strict_types=1);

use App\Domain\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BMTestModel extends BaseModel
{
    protected $table = 'bm_test';
}

describe('BaseModel', function () {
    it('is abstract', function () {
        expect((new ReflectionClass(BaseModel::class))->isAbstract())->toBeTrue();
    });

    it('uses HasUuids trait', function () {
        expect(class_uses_recursive(BaseModel::class))->toContain(HasUuids::class);
    });

    it('is non-incrementing with string key type', function () {
        $model = new BMTestModel;

        expect($model->getIncrementing())->toBeFalse()
            ->and($model->getKeyType())->toBe('string');
    });

    it('extends Eloquent Model', function () {
        expect(new BMTestModel)->toBeInstanceOf(Model::class);
    });
});
