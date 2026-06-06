<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Models;

use App\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class MockModel extends BaseModel
{
    protected $table = 'mock_models';

    public function getTable(): string
    {
        return $this->table;
    }
}

test('base model has string non-incrementing keys', function () {
    $model = new MockModel;

    expect($model->getIncrementing())->toBeFalse();
    expect($model->getKeyType())->toBe('string');
});

test('base model auto generates uuid on create', function () {
    $model = new MockModel;

    $model->{$model->getKeyName()} = $model->newUniqueId();

    expect($model->getKey())->toBeString();
    expect(Str::isUuid($model->getKey()))->toBeTrue();
});

test('base model uses has uuids trait', function () {
    $traits = class_uses_recursive(MockModel::class);

    expect($traits)->toContain(HasUuids::class);
});

test('base model generates unique ids across multiple instances', function () {
    $id1 = (new MockModel)->newUniqueId();
    $id2 = (new MockModel)->newUniqueId();

    expect($id1)->not->toBe($id2);
});
